const apiBase = "../api";
let incidents = [];

let typeLabels = {};

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

let barangays = [];

const map = L.map("map").setView([16.455, 120.59], 12);
L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap contributors"
}).addTo(map);

const markersLayer = L.layerGroup().addTo(map);
const typeFilters = document.getElementById("type-filters");
const barangayFilter = document.getElementById("barangay-filter");
const searchInput = document.getElementById("search-input");
const dateStart = document.getElementById("date-start");
const dateEnd = document.getElementById("date-end");
const statusFilter = document.getElementById("status-filter");
const detailsPanel = document.getElementById("details-panel");
const detailsTitle = document.getElementById("details-title");
const detailsBody = document.getElementById("details-body");
const detailsActions = document.querySelector(".details-actions");
const markerStyleButtons = document.querySelectorAll(".toggle-btn");
const reportPanel = document.getElementById("report-panel");
const reportForm = document.getElementById("report-form");
const reportType = document.getElementById("report-type");
const reportTitle = document.getElementById("report-title");
const reportDescription = document.getElementById("report-description");
const reportBarangay = document.getElementById("report-barangay");
const reportDate = document.getElementById("report-date");
const reportTime = document.getElementById("report-time");
const reportSeverity = document.getElementById("report-severity");
const reportCoords = document.getElementById("report-coords");
const reportStatus = document.getElementById("report-status");
const reportButton = document.getElementById("report-crime");
const reportClose = document.getElementById("close-report");
const reportCancel = document.getElementById("report-cancel");

let markerStyle = "dot";
let activeTypes = new Set();
let reportLatLng = null;
let reportTypes = [];

function buildTypeFilters() {
    typeFilters.innerHTML = "";
    Object.entries(typeLabels).forEach(([key, label]) => {
        const wrapper = document.createElement("label");
        wrapper.className = "checkbox-item";
        wrapper.innerHTML = `
            <input type="checkbox" data-type="${key}" checked />
            <span>${label}</span>
        `;
        typeFilters.appendChild(wrapper);
    });

    typeFilters.querySelectorAll("input[type='checkbox']").forEach((input) => {
        input.addEventListener("change", () => {
            const type = input.getAttribute("data-type");
            if (input.checked) {
                activeTypes.add(type);
            } else {
                activeTypes.delete(type);
            }
        });
    });
}

function buildBarangayOptions() {
    barangayFilter.innerHTML = '<option value="">All barangays</option>';
    barangays.forEach((barangay) => {
        const option = document.createElement("option");
        option.value = barangay;
        option.textContent = barangay;
        barangayFilter.appendChild(option);
    });
}

function buildReportTypeOptions() {
    reportType.innerHTML = "";
    reportTypes.forEach((type) => {
        const option = document.createElement("option");
        option.value = type.crime_type_id;
        option.textContent = `${type.type_name} (${type.category.replace(/_/g, " ")})`;
        reportType.appendChild(option);
    });
}

function buildReportBarangayOptions() {
    reportBarangay.innerHTML = "";
    barangays.forEach((barangay) => {
        const option = document.createElement("option");
        option.value = barangay;
        option.textContent = barangay;
        reportBarangay.appendChild(option);
    });
}

function formatStatus(status) {
    return status.replace(/_/g, " ");
}

function isWithinDateRange(dateString) {
    const start = dateStart.value ? new Date(dateStart.value) : null;
    const end = dateEnd.value ? new Date(dateEnd.value) : null;
    const date = new Date(dateString);

    if (start && date < start) {
        return false;
    }
    if (end && date > end) {
        return false;
    }
    return true;
}

function createMarker(incident) {
    if (markerStyle === "icon") {
        return L.marker([incident.lat, incident.lng]);
    }
    return L.circleMarker([incident.lat, incident.lng], {
        radius: 7,
        color: typeColors[incident.type] || "#22d3ee",
        fillOpacity: 0.85
    });
}

function renderMarkers() {
    markersLayer.clearLayers();

    incidents
        .filter((incident) => activeTypes.has(incident.type))
        .filter((incident) => (barangayFilter.value ? incident.barangay === barangayFilter.value : true))
        .filter((incident) => (statusFilter.value ? incident.status === statusFilter.value : true))
        .filter((incident) => isWithinDateRange(incident.date))
        .forEach((incident) => {
            const marker = createMarker(incident).addTo(markersLayer);
            marker.bindTooltip(
                `<strong>${incident.title}</strong><br>${incident.barangay} • ${incident.date}<br>${incident.description}`,
                { direction: "top" }
            );
            marker.on("click", () => showDetails(incident));
        });
}

function showDetails(incident) {
    detailsPanel.classList.add("is-open");
    detailsTitle.textContent = incident.title;
    detailsBody.innerHTML = `
        <p><strong>Barangay:</strong> ${incident.barangay}</p>
        <p><strong>Date:</strong> ${incident.date}</p>
        <p><strong>Status:</strong> ${formatStatus(incident.status)}</p>
        <p><strong>Severity:</strong> ${incident.severity}</p>
        <p class="muted">${incident.description}</p>
    `;
}

function openReportPanel() {
    reportPanel.classList.add("is-open");
    detailsBody.classList.add("is-hidden");
    detailsActions.classList.add("is-hidden");
    reportStatus.textContent = "";
}

function closeReportPanel() {
    reportPanel.classList.remove("is-open");
    detailsBody.classList.remove("is-hidden");
    detailsActions.classList.remove("is-hidden");
    reportStatus.textContent = "";
}

function setReportCoords(latlng) {
    reportLatLng = latlng;
    reportCoords.value = `${latlng.lat.toFixed(5)}, ${latlng.lng.toFixed(5)}`;
}

function resetFilters() {
    activeTypes = new Set(Object.keys(typeLabels));
    buildTypeFilters();
    barangayFilter.value = "";
    statusFilter.value = "";
    dateStart.value = "";
    dateEnd.value = "";
    searchInput.value = "";
}

async function loadFilters() {
    try {
        const response = await fetch(`${apiBase}/filters.php`);
        const payload = await response.json();
        if (!payload.ok) {
            return;
        }

        reportTypes = payload.data.types;
        typeLabels = payload.data.types.reduce((acc, item) => {
            if (!acc[item.category]) {
                acc[item.category] = item.category.replace(/_/g, " ");
            }
            return acc;
        }, {});

        barangays = payload.data.barangays;
        buildTypeFilters();
        buildBarangayOptions();
        buildReportTypeOptions();
        buildReportBarangayOptions();

        if (payload.data.date_range.min) {
            dateStart.value = payload.data.date_range.min.slice(0, 10);
        }
        if (payload.data.date_range.max) {
            dateEnd.value = payload.data.date_range.max.slice(0, 10);
        }

        resetFilters();
    } catch (error) {
        console.error("Failed to load filters", error);
    }
}

function buildQueryParams() {
    const params = new URLSearchParams();
    if (activeTypes.size) {
        params.set("types", Array.from(activeTypes).join(","));
    }
    if (barangayFilter.value) {
        params.set("barangay", barangayFilter.value);
    }
    if (statusFilter.value) {
        params.set("status", statusFilter.value);
    }
    if (dateStart.value) {
        params.set("date_start", dateStart.value);
    }
    if (dateEnd.value) {
        params.set("date_end", dateEnd.value);
    }
    if (searchInput.value.trim()) {
        params.set("search", searchInput.value.trim());
    }
    return params.toString();
}

async function loadIncidents() {
    try {
        const query = buildQueryParams();
        const response = await fetch(`${apiBase}/incidents.php?${query}`);
        const payload = await response.json();
        incidents = payload.ok ? payload.data : [];
        renderMarkers();
    } catch (error) {
        console.error("Failed to load incidents", error);
        incidents = [];
        renderMarkers();
    }
}

markerStyleButtons.forEach((button) => {
    button.addEventListener("click", () => {
        markerStyleButtons.forEach((btn) => btn.classList.remove("is-active"));
        button.classList.add("is-active");
        markerStyle = button.getAttribute("data-style");
        renderMarkers();
    });
});

document.getElementById("apply-filters").addEventListener("click", loadIncidents);
document.getElementById("reset-filters").addEventListener("click", () => {
    resetFilters();
    loadIncidents();
});

document.getElementById("close-details").addEventListener("click", () => {
    detailsPanel.classList.remove("is-open");
});

reportButton.addEventListener("click", openReportPanel);
reportClose.addEventListener("click", closeReportPanel);
reportCancel.addEventListener("click", closeReportPanel);

map.on("click", (event) => {
    if (!reportPanel.classList.contains("is-open")) {
        return;
    }
    setReportCoords(event.latlng);
});

reportForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    if (!reportLatLng) {
        reportStatus.textContent = "Please click on the map to set the report location.";
        return;
    }

    const payload = {
        crime_type_id: reportType.value,
        title: reportTitle.value.trim(),
        description: reportDescription.value.trim(),
        barangay: reportBarangay.value,
        occurred_date: reportDate.value,
        occurred_time: reportTime.value,
        severity: reportSeverity.value,
        latitude: reportLatLng.lat,
        longitude: reportLatLng.lng
    };

    reportStatus.textContent = "Submitting report...";
    try {
        const response = await fetch(`${apiBase}/report.php`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(payload)
        });
        const result = await response.json();
        if (!result.ok) {
            reportStatus.textContent = result.error || "Submission failed.";
            return;
        }

        reportStatus.textContent = "Report submitted. Awaiting verification.";
        reportForm.reset();
        reportLatLng = null;
        reportCoords.value = "";
        loadIncidents();
    } catch (error) {
        console.error("Report submission failed", error);
        reportStatus.textContent = "Submission failed. Please try again.";
    }
});

loadFilters().then(loadIncidents);
