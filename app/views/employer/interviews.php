
<div class="ei-page">

    <!-- ══════════════ PAGE HEADER ══════════════ -->
    <div class="ei-header">
        <div class="ei-header-left">
            <div class="ei-header-icon">
                <i class="fa-solid fa-video"></i>
            </div>
            <div>
                <h1 class="ei-title gradient-text">Interviews</h1>
                <p class="ei-subtitle">Manage your scheduled, completed and cancelled interviews</p>
            </div>
        </div>
        <div class="ei-header-right">
            <div class="ei-stats-chips" id="eiStatsChips">
                <span class="ei-chip ei-chip--upcoming" id="statUpcoming">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span id="statUpcomingCount">—</span> Upcoming
                </span>
                <span class="ei-chip ei-chip--completed" id="statCompleted">
                    <i class="fa-solid fa-circle-check"></i>
                    <span id="statCompletedCount">—</span> Completed
                </span>
                <span class="ei-chip ei-chip--cancelled" id="statCancelled">
                    <i class="fa-solid fa-circle-xmark"></i>
                    <span id="statCancelledCount">—</span> Cancelled
                </span>
            </div>
        </div>
    </div>

    <!-- ══════════════ FILTERS ROW ══════════════ -->
    <div class="ei-filters">
        <div class="ei-filter-tabs" id="eiFilterTabs">
            <button class="ei-tab active" data-status="all">
                <i class="fa-solid fa-border-all"></i> All
            </button>
            <button class="ei-tab" data-status="upcoming">
                <i class="fa-solid fa-calendar-check"></i> Upcoming
            </button>
            <button class="ei-tab" data-status="completed">
                <i class="fa-solid fa-circle-check"></i> Completed
            </button>
            <button class="ei-tab" data-status="cancelled">
                <i class="fa-solid fa-circle-xmark"></i> Cancelled
            </button>
        </div>
        <div class="ei-search-wrap">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input
                type="text"
                id="searchFilter"
                placeholder="Search candidate or job title..."
                autocomplete="off"
            />
        </div>
    </div>

    <!-- ══════════════ INTERVIEWS GRID ══════════════ -->
    <div id="interviewsGrid" class="ei-grid">
        <!-- Skeletons rendered on load by JS -->
        <div class="ei-card ei-skeleton">
            <div class="ei-card-header"><div class="ei-avatar-skeleton"></div><div class="ei-info-skeleton"><span></span><span></span><span></span></div></div>
            <div class="ei-detail-row-skeleton"></div>
            <div class="ei-actions-skeleton"></div>
        </div>
        <div class="ei-card ei-skeleton">
            <div class="ei-card-header"><div class="ei-avatar-skeleton"></div><div class="ei-info-skeleton"><span></span><span></span><span></span></div></div>
            <div class="ei-detail-row-skeleton"></div>
            <div class="ei-actions-skeleton"></div>
        </div>
        <div class="ei-card ei-skeleton">
            <div class="ei-card-header"><div class="ei-avatar-skeleton"></div><div class="ei-info-skeleton"><span></span><span></span><span></span></div></div>
            <div class="ei-detail-row-skeleton"></div>
            <div class="ei-actions-skeleton"></div>
        </div>
    </div>

    <!-- Empty state (shown by JS) -->
    <div class="ei-empty" id="eiEmpty" style="display:none">
        <div class="ei-empty-icon"><i class="fa-regular fa-calendar-xmark"></i></div>
        <h2>No interviews found</h2>
        <p>Try adjusting your filters, or check back once interviews have been scheduled.</p>
        <button class="btn btn-secondary btn-sm" id="eiResetFilters">
            <i class="fa-solid fa-rotate-left"></i> Reset filters
        </button>
    </div>

</div>

<!-- ══════════════ RESCHEDULE MODAL ══════════════ -->
<div id="rescheduleModal" class="ei-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="rescheduleModalTitle">
    <div class="ei-modal">

        <div class="ei-modal-header">
            <h3 id="rescheduleModalTitle">
                <i class="fa-solid fa-calendar-pen"></i> Reschedule Interview
            </h3>
            <button class="ei-modal-close" id="rescheduleModalClose" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Candidate summary strip -->
        <div class="ei-modal-candidate" id="rescheduleCandidateInfo">
            <!-- Filled by JS -->
        </div>

        <div class="ei-modal-body">
            <form id="rescheduleForm" novalidate>

                <div class="ei-form-row">
                    <div class="ei-form-group">
                        <label for="rescheduleDateTime">
                            <i class="fa-regular fa-calendar"></i> New Date &amp; Time <span class="ei-required">*</span>
                        </label>
                        <input type="datetime-local" id="rescheduleDateTime" class="ei-input" required />
                        <span class="ei-field-hint">All times are in your local timezone</span>
                    </div>
                    <div class="ei-form-group">
                        <label for="rescheduleDuration">
                            <i class="fa-regular fa-clock"></i> Duration
                        </label>
                        <select id="rescheduleDuration" class="ei-input">
                            <option value="15">15 minutes</option>
                            <option value="30" selected>30 minutes</option>
                            <option value="45">45 minutes</option>
                            <option value="60">1 hour</option>
                            <option value="90">1.5 hours</option>
                            <option value="120">2 hours</option>
                        </select>
                    </div>
                </div>

                <div class="ei-form-group">
                    <label for="rescheduleType">
                        <i class="fa-solid fa-layer-group"></i> Interview Type <span class="ei-required">*</span>
                    </label>
                    <div class="ei-type-toggle" id="rescheduleTypeGroup">
                        <button type="button" class="ei-type-btn active" data-value="video">
                            <i class="fa-solid fa-video"></i> Video Call
                        </button>
                        <button type="button" class="ei-type-btn" data-value="phone">
                            <i class="fa-solid fa-phone"></i> Phone Call
                        </button>
                        <button type="button" class="ei-type-btn" data-value="in_person">
                            <i class="fa-solid fa-building"></i> In Person
                        </button>
                    </div>
                    <input type="hidden" id="rescheduleType" value="video" />
                </div>

                <div class="ei-form-group" id="rescheduleLinkGroup">
                    <label for="rescheduleLink">
                        <i class="fa-solid fa-link"></i> Meeting Link
                    </label>
                    <input type="url" id="rescheduleLink" class="ei-input" placeholder="https://meet.google.com/..." />
                </div>

                <div class="ei-form-group" id="rescheduleLocationGroup" style="display:none">
                    <label for="rescheduleLocation">
                        <i class="fa-solid fa-location-dot"></i> Location
                    </label>
                    <input type="text" id="rescheduleLocation" class="ei-input" placeholder="e.g. 3rd Floor, Westlands Office Park" />
                </div>

                <div class="ei-form-group">
                    <label for="rescheduleNotes">
                        <i class="fa-solid fa-note-sticky"></i> Notes for Candidate
                    </label>
                    <textarea id="rescheduleNotes" class="ei-input ei-textarea" rows="3" placeholder="Any preparation tips, dress code, what to bring..."></textarea>
                </div>

            </form>
        </div>

        <div class="ei-modal-footer">
            <button type="button" class="btn btn-secondary" id="rescheduleCancelBtn">Cancel</button>
            <button type="button" class="btn btn-primary" id="rescheduleSubmitBtn">
                <i class="fa-solid fa-calendar-check"></i> Update Interview
            </button>
        </div>

    </div>
</div>