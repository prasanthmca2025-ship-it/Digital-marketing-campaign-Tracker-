/**
 * Custom JavaScript for Digital Marketing Campaign Tracker
 * Handles UI interactions, live calculations, dynamic modals, and client validation.
 */

document.addEventListener('DOMContentLoaded', function () {
    // -------------------------------------------------------------
    // 1. Mobile Sidebar Toggle
    // -------------------------------------------------------------
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (sidebarToggle && sidebar && sidebarOverlay) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
            sidebarOverlay.classList.toggle('show');
        });

        sidebarOverlay.addEventListener('click', function () {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        });
    }

    // -------------------------------------------------------------
    // 2. Delete Campaign Modal Population
    // -------------------------------------------------------------
    const deleteModal = document.getElementById('deleteCampaignModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            const campaignId = button.getAttribute('data-campaign-id');
            const campaignName = button.getAttribute('data-campaign-name');

            const modalCampaignName = deleteModal.querySelector('#deleteModalCampaignName');
            const modalCampaignId = deleteModal.querySelector('#deleteModalCampaignId');

            if (modalCampaignName) {
                modalCampaignName.textContent = campaignName || 'this campaign';
            }
            if (modalCampaignId) {
                modalCampaignId.value = campaignId || '';
            }
        });
    }

    // -------------------------------------------------------------
    // 3. Dynamic Calculation for Add Metrics Form
    // -------------------------------------------------------------
    const impressionsInput = document.getElementById('metric_impressions');
    const clicksInput = document.getElementById('metric_clicks');
    const conversionsInput = document.getElementById('metric_conversions');
    const ctrDisplay = document.getElementById('calc_ctr_display');
    const cvrDisplay = document.getElementById('calc_cvr_display');

    function updateCalculatedMetrics() {
        if (!impressionsInput || !clicksInput || !conversionsInput) return;

        const impressions = parseFloat(impressionsInput.value) || 0;
        const clicks = parseFloat(clicksInput.value) || 0;
        const conversions = parseFloat(conversionsInput.value) || 0;

        // Calculate CTR: (clicks / impressions) * 100
        let ctr = 0;
        if (impressions > 0) {
            ctr = (clicks / impressions) * 100;
        }

        // Calculate CVR: (conversions / clicks) * 100
        let cvr = 0;
        if (clicks > 0) {
            cvr = (conversions / clicks) * 100;
        }

        if (ctrDisplay) {
            ctrDisplay.textContent = ctr.toFixed(2) + '%';
        }
        if (cvrDisplay) {
            cvrDisplay.textContent = cvr.toFixed(2) + '%';
        }
    }

    if (impressionsInput && clicksInput && conversionsInput) {
        impressionsInput.addEventListener('input', updateCalculatedMetrics);
        clicksInput.addEventListener('input', updateCalculatedMetrics);
        conversionsInput.addEventListener('input', updateCalculatedMetrics);
        // Initial run in case form has pre-filled values
        updateCalculatedMetrics();
    }

    // -------------------------------------------------------------
    // 4. Date Validation for Campaign Forms
    // -------------------------------------------------------------
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const campaignForm = document.getElementById('campaignForm');

    if (startDateInput && endDateInput && campaignForm) {
        campaignForm.addEventListener('submit', function (event) {
            const startVal = new Date(startDateInput.value);
            const endVal = new Date(endDateInput.value);

            if (startDateInput.value && endDateInput.value && endVal < startVal) {
                event.preventDefault();
                alert('End Date cannot be earlier than Start Date.');
                endDateInput.focus();
            }
        });
    }

    // -------------------------------------------------------------
    // 5. Client-Side Quick Table Search / Filter (if enabled)
    // -------------------------------------------------------------
    const liveSearchInput = document.getElementById('tableLiveSearch');
    const filterTable = document.getElementById('campaignsDataTable');

    if (liveSearchInput && filterTable) {
        liveSearchInput.addEventListener('keyup', function () {
            const filter = liveSearchInput.value.toLowerCase();
            const rows = filterTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const textContent = rows[i].textContent.toLowerCase();
                if (textContent.indexOf(filter) > -1) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        });
    }
});
