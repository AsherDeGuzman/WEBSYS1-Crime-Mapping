const apiBase = "../api";

const typeColors = {
    violent: "#f43f5e",
    property: "#facc15",
    drug: "#a855f7",
    traffic: "#22d3ee",
    cybercrime: "#38bdf8",
    white_collar: "#f97316",
    public_order: "#34d399",
    status_offense: "#fb7185"
};

const miniMap = L.map("mini-map", { zoomControl: false, scrollWheelZoom: false }).setView([16.455, 120.59], 12);
L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap contributors"
}).addTo(miniMap);

const miniMarkers = L.layerGroup().addTo(miniMap);

const feedContainer = document.getElementById("recent-feed");
const alertsContainer = document.getElementById("alerts");

function formatNotificationLabel(type) {
    const labels = {
        new_report: "New report",
        status_update: "Status update",
        high_severity: "High severity",
        mention: "Mention"
    };

    return labels[type] || "Notification";
}

function renderFeed(incidents) {
    feedContainer.innerHTML = "";
    if (!incidents.length) {
        feedContainer.innerHTML = "<div class=\"feed-item\">No reports yet.</div>";
        return;
    }

    incidents.slice(0, 3).forEach((incident) => {
        const item = document.createElement("div");
        item.className = "feed-item";
        item.innerHTML = `
            <h3>${incident.title}</h3>
            <div class="feed-meta">${incident.barangay} • ${incident.date} • ${incident.status.replace(/_/g, " ")}</div>
            <p class="muted">${incident.description}</p>
        `;
        feedContainer.appendChild(item);
    });
}

function renderAlerts(alerts, notifications = []) {
    alertsContainer.innerHTML = "";
    const feedItems = [
        ...alerts.map((incident) => ({
            kind: "alert",
            title: incident.title,
            subtitle: `High severity • ${incident.barangay}`,
            meta: incident.date
        })),
        ...notifications.map((notification) => ({
            kind: "notification",
            title: notification.message,
            subtitle: `${formatNotificationLabel(notification.notification_type)} • ${notification.barangay || "Municipal"}`,
            meta: notification.created_at
        }))
    ];

    if (!feedItems.length) {
        alertsContainer.innerHTML = "<div class=\"alert-item\">No alerts or notifications yet.</div>";
        return;
    }

    feedItems.forEach((entry) => {
        const itemElement = document.createElement("div");
        itemElement.className = "alert-item";
        itemElement.innerHTML = entry.kind === "alert"
            ? `
                <strong>${entry.title}</strong>
                <div class="alert-meta">${entry.subtitle}</div>
                <div class="alert-time">${entry.meta}</div>
            `
            : `
                <strong>${entry.title}</strong>
                <div class="alert-meta">${entry.subtitle}</div>
                <div class="alert-time">${entry.meta}</div>
            `;
        alertsContainer.appendChild(itemElement);
    });
}

function updateStats(stats) {
    document.querySelector('[data-stat="daily"]').textContent = stats.daily ?? 0;
    document.querySelector('[data-stat="active"]').textContent = stats.active ?? 0;
    document.querySelector('[data-stat="hotspot"]').textContent = stats.hotspot ?? "-";
}

function renderMiniMap(incidents) {
    miniMarkers.clearLayers();
    incidents.forEach((incident) => {
        if (!incident.lat || !incident.lng) {
            return;
        }
        const marker = L.circleMarker([incident.lat, incident.lng], {
            radius: 6,
            color: typeColors[incident.type] || "#22d3ee",
            fillOpacity: 0.8
        }).addTo(miniMarkers);
        marker.bindTooltip(`${incident.title}<br>${incident.barangay}`, { direction: "top" });
    });
}

async function loadDashboard() {
    try {
        const [statsRes, incidentsRes, alertsRes, notificationsRes] = await Promise.all([
            fetch(`${apiBase}/stats.php`),
            fetch(`${apiBase}/incidents.php?limit=6`),
            fetch(`${apiBase}/alerts.php`),
            fetch(`${apiBase}/notifications.php`)
        ]);

        const statsJson = await statsRes.json();
        const incidentsJson = await incidentsRes.json();
        const alertsJson = await alertsRes.json();
        const notificationsJson = await notificationsRes.json();

        const incidents = incidentsJson.ok ? incidentsJson.data : [];
        const alerts = alertsJson.ok ? alertsJson.data : [];
        const notifications = notificationsJson.ok ? notificationsJson.data : [];
        const stats = statsJson.ok ? statsJson.data : { daily: 0, active: 0, hotspot: "-" };

        updateStats(stats);
        renderFeed(incidents);
        renderAlerts(alerts, notifications);
        renderMiniMap(incidents);
    } catch (error) {
        console.error("Failed to load dashboard data", error);
        updateStats({ daily: 0, active: 0, hotspot: "-" });
        renderFeed([]);
        renderAlerts([]);
        renderMiniMap([]);
    }
}

document.getElementById("refresh-feed").addEventListener("click", loadDashboard);

loadDashboard();
