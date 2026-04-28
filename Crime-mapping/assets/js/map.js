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

const typeIconUrls = {
    violent: "../assets/images/icons/violent.svg",
    property: "../assets/images/icons/property.svg",
    drug: "../assets/images/icons/drug.svg",
    traffic: "../assets/images/icons/traffic.svg",
    cybercrime: "../assets/images/icons/cybercrime.svg",
    white_collar: "../assets/images/icons/white_collar.svg",
    public_order: "../assets/images/icons/public_order.svg",
    status_offense: "../assets/images/icons/status_offense.svg"
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
const credibleBtn = document.getElementById("credible-btn");
const notCredibleBtn = document.getElementById("not-credible-btn");
const credibleCount = document.getElementById("credible-count");
const notCredibleCount = document.getElementById("not-credible-count");
const detailModal = document.getElementById("detail-modal");
const modalTitle = document.getElementById("modal-title");
const detailInfo = document.getElementById("detail-info");
const imageCarousel = document.getElementById("image-carousel");
const detailImageInput = document.getElementById("detail-image-input");
const uploadStatus = document.getElementById("upload-status");
const closeModal = document.getElementById("close-modal");

let markerStyle = "dot";
let activeTypes = new Set();
let reportLatLng = null;
let reportTypes = [];
let currentIncidentId = null;
let filterTimer = null;

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

            scheduleLoadIncidents();
        });
    });
}

function scheduleLoadIncidents() {
    if (filterTimer) {
        clearTimeout(filterTimer);
    }

    filterTimer = setTimeout(() => {
        loadIncidents();
    }, 150);
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
        return L.marker([incident.lat, incident.lng], {
            icon: L.icon({
                iconUrl: typeIconUrls[incident.type] || "../assets/images/icons/violent.svg",
                iconSize: [32, 32],
                iconAnchor: [16, 32],
                popupAnchor: [0, -28]
            })
        });
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
            marker.on("click", () => openDetailModal(incident));
        });
}

function showDetails(incident) {
    currentIncidentId = incident.id;
    detailsPanel.classList.add("is-open");
    detailsTitle.textContent = "Selected report";
    detailsBody.innerHTML = `
        <p><strong>${incident.title}</strong></p>
        <p class="muted">Full details are shown in the detail panel.</p>
    `;
}

async function loadIncidentDetail(incidentId) {
    try {
        const response = await fetch(`${apiBase}/incident-detail.php?incident_id=${incidentId}`);
        const data = await response.json();

        if (!data.ok) {
            detailInfo.innerHTML = '<p class="muted">Failed to load incident details.</p>';
            return;
        }

        const incident = data.incident;
        modalTitle.textContent = incident.title;

        detailInfo.innerHTML = `
            <div>
                <p><strong>Barangay:</strong> ${incident.barangay}</p>
                <p><strong>Date:</strong> ${incident.occurred_at}</p>
                <p><strong>Status:</strong> ${formatStatus(incident.status)}</p>
                <p><strong>Severity:</strong> ${incident.severity}</p>
                <p><strong>Type:</strong> ${incident.type_name}</p>
                ${incident.reported_by ? `<p><strong>Reported by:</strong> ${incident.reported_by}</p>` : ''}
                <p class="muted" style="margin-top: 12px;">${incident.description}</p>
            </div>
        `;

        renderImages(data.images);
    } catch (error) {
        console.error("Failed to load incident detail", error);
        detailInfo.innerHTML = '<p class="muted">Failed to load incident details.</p>';
    }
}

function renderImages(images) {
    imageCarousel.innerHTML = "";
    if (!images || images.length === 0) {
        imageCarousel.innerHTML = '<p class="muted">No images uploaded yet.</p>';
        return;
    }

    images.forEach((img) => {
        const imgElement = document.createElement("img");
        imgElement.src = "../" + img.file_path;
        imgElement.alt = "Evidence";
        imgElement.className = "image-thumbnail";
        imgElement.addEventListener("click", () => viewImageFull(img.file_path));
        imageCarousel.appendChild(imgElement);
    });
}

function viewImageFull(filePath) {
    window.open("../" + filePath, "_blank");
}

function openDetailModal(incident) {
    showDetails(incident);
    currentIncidentId = incident.id;
    detailModal.classList.add("is-open");
    uploadStatus.textContent = "";
    loadValidationCounts();
    loadIncidentDetail(incident.id);
}

function closeDetailModal() {
    detailModal.classList.remove("is-open");
    currentIncidentId = null;
    uploadStatus.textContent = "";
}

function openReportPanel() {
    reportPanel.classList.add("is-open");
    detailsBody.classList.add("is-hidden");
    reportStatus.textContent = "";
}

function closeReportPanel() {
    reportPanel.classList.remove("is-open");
    detailsBody.classList.remove("is-hidden");
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

async function loadValidationCounts() {
    if (!currentIncidentId) {
        return;
    }

    try {
        const response = await fetch(`${apiBase}/validate-report.php?incident_id=${currentIncidentId}`, {
            method: "GET"
        });
        
        if (response.ok) {
            const data = await response.json();
            if (data.ok) {
                credibleCount.textContent = data.credible || 0;
                notCredibleCount.textContent = data.not_credible || 0;

                credibleBtn.classList.remove("is-active");
                notCredibleBtn.classList.remove("is-active");

                if (data.user_reaction === "credible") {
                    credibleBtn.classList.add("is-active");
                } else if (data.user_reaction === "not_credible") {
                    notCredibleBtn.classList.add("is-active");
                }
            }
        }
    } catch (error) {
        console.error("Failed to load validation counts", error);
    }
}

async function submitValidation(reaction) {
    if (!currentIncidentId) {
        return;
    }

    try {
        const response = await fetch(`${apiBase}/validate-report.php`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                incident_id: currentIncidentId,
                reaction: reaction
            })
        });

        const data = await response.json();
        if (data.ok) {
            credibleCount.textContent = data.credible || 0;
            notCredibleCount.textContent = data.not_credible || 0;

            // Update button active states
            credibleBtn.classList.remove("is-active");
            notCredibleBtn.classList.remove("is-active");

            if (data.user_reaction === "credible") {
                credibleBtn.classList.add("is-active");
            } else if (data.user_reaction === "not_credible") {
                notCredibleBtn.classList.add("is-active");
            }
        }
    } catch (error) {
        console.error("Failed to submit validation", error);
    }
}

credibleBtn.addEventListener("click", () => {
    submitValidation("credible");
});

notCredibleBtn.addEventListener("click", () => {
    submitValidation("not_credible");
});

searchInput.addEventListener("input", scheduleLoadIncidents);
barangayFilter.addEventListener("change", loadIncidents);
statusFilter.addEventListener("change", loadIncidents);
dateStart.addEventListener("change", loadIncidents);
dateEnd.addEventListener("change", loadIncidents);

document.getElementById("reset-filters").addEventListener("click", () => {
    resetFilters();
    loadIncidents();
});

document.getElementById("close-details").addEventListener("click", () => {
    detailsPanel.classList.remove("is-open");
});

closeModal.addEventListener("click", closeDetailModal);

detailImageInput.addEventListener("change", async (event) => {
    const file = event.target.files[0];
    if (!file || !currentIncidentId) {
        return;
    }

    const formData = new FormData();
    formData.append("incident_id", currentIncidentId);
    formData.append("image", file);

    uploadStatus.textContent = "Uploading...";

    try {
        const response = await fetch(`${apiBase}/upload-image.php`, {
            method: "POST",
            body: formData
        });

        const result = await response.json();
        if (!result.ok) {
            uploadStatus.textContent = result.error || "Upload failed.";
            return;
        }

        uploadStatus.textContent = "Image uploaded successfully.";
        detailImageInput.value = "";
        loadIncidentDetail(currentIncidentId);
    } catch (error) {
        console.error("Image upload failed", error);
        uploadStatus.textContent = "Upload failed. Please try again.";
    }
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
