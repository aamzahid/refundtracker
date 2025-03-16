<?php
// Exit if accessed directly
if (!defined("ABSPATH")) {
    exit;
}
?>
<div class="refund-tracker-container">
    <h2><?php _e("Refund Tracker", "refund-tracker"); ?></h2>
    
    <div class="refund-tracker-tabs">
        <ul class="nav-tabs">
            <li class="active"><a href="#refund-table" data-toggle="tab"><?php _e("Refund Table", "refund-tracker"); ?></a></li>
            <li><a href="#add-refund" data-toggle="tab"><?php _e("Add New Refund", "refund-tracker"); ?></a></li>
        </ul>
        
        <div class="tab-content">
            <!-- Refund Table Tab -->
            <div id="refund-table" class="tab-pane active">
                <div class="refund-summary">
                    <div class="summary-box main-refund">
                        <h3><?php _e("Main Refunds", "refund-tracker"); ?></h3>
                        <div class="amount">$<span id="main-refund-total">0.00</span></div>
                    </div>
                    <div class="summary-box recurring-refund">
                        <h3><?php _e("Recurring Refunds", "refund-tracker"); ?></h3>
                        <div class="amount">$<span id="recurring-refund-total">0.00</span></div>
                    </div>
                    <div class="summary-box total-refund">
                        <h3><?php _e("Total Refunds", "refund-tracker"); ?></h3>
                        <div class="amount">$<span id="total-refund-amount">0.00</span></div>
                    </div>
                    <div class="summary-box not-refunded">
                        <h3><?php _e("Not Refunded", "refund-tracker"); ?></h3>
                        <div class="amount">$<span id="not-refunded-total">0.00</span></div>
                    </div>
                </div>
                
                <div class="refund-filters">
                    <div class="filter-group">
                        <label for="date-filter-start"><?php _e("Date Range:", "refund-tracker"); ?></label>
                        <input type="text" id="date-filter-start" class="datepicker" placeholder="<?php _e("Start Date", "refund-tracker"); ?>">
                        <span><?php _e("to", "refund-tracker"); ?></span>
                        <input type="text" id="date-filter-end" class="datepicker" placeholder="<?php _e("End Date", "refund-tracker"); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="email-filter"><?php _e("Email:", "refund-tracker"); ?></label>
                        <input type="text" id="email-filter" placeholder="<?php _e("Filter by email", "refund-tracker"); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="type-filter"><?php _e("Type:", "refund-tracker"); ?></label>
                        <select id="type-filter">
                            <option value="all"><?php _e("All Types", "refund-tracker"); ?></option>
                            <option value="main"><?php _e("Main", "refund-tracker"); ?></option>
                            <option value="recurring"><?php _e("Recurring", "refund-tracker"); ?></option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="status-filter"><?php _e("Status:", "refund-tracker"); ?></label>
                        <select id="status-filter">
                            <option value="all"><?php _e("All Statuses", "refund-tracker"); ?></option>
                            <option value="refunded"><?php _e("Refunded", "refund-tracker"); ?></option>
                            <option value="not_refunded"><?php _e("Not Refunded", "refund-tracker"); ?></option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <button id="apply-filters" class="button"><?php _e("Apply Filters", "refund-tracker"); ?></button>
                        <button id="reset-filters" class="button"><?php _e("Reset", "refund-tracker"); ?></button>
                    </div>
                </div>
                
                <div class="refund-table-wrapper">
                    <table id="refund-table-data" class="refund-tracker-table">
                        <thead>
                            <tr>
                                <th data-sort="date"><?php _e("Date", "refund-tracker"); ?> <span class="sort-icon"></span></th>
                                <th data-sort="email"><?php _e("Email", "refund-tracker"); ?> <span class="sort-icon"></span></th>
                                <th data-sort="refund_type"><?php _e("Type", "refund-tracker"); ?> <span class="sort-icon"></span></th>
                                <th data-sort="amount"><?php _e("Amount", "refund-tracker"); ?> <span class="sort-icon"></span></th>
                                <th data-sort="status"><?php _e("Status", "refund-tracker"); ?> <span class="sort-icon"></span></th>
                                <th><?php _e("Actions", "refund-tracker"); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded via AJAX -->
                        </tbody>
                    </table>
                    <div id="no-refunds-message" class="hidden">
                        <?php _e("No refunds found matching your criteria.", "refund-tracker"); ?>
                    </div>
                </div>
            </div>
            
            <!-- Add New Refund Tab -->
            <div id="add-refund" class="tab-pane">
                <form id="add-refund-form" class="refund-form">
                    <div class="form-group">
                        <label for="refund-date"><?php _e("Date:", "refund-tracker"); ?></label>
                        <input type="text" id="refund-date" class="datepicker" placeholder="<?php _e("Select date", "refund-tracker"); ?>">
                    </div>
                    <div class="form-group">
                        <label for="refund-email"><?php _e("Email Address:", "refund-tracker"); ?> <span class="required">*</span></label>
                        <input type="email" id="refund-email" required placeholder="<?php _e("customer@example.com", "refund-tracker"); ?>">
                    </div>
                    <div class="form-group">
                        <label for="refund-type"><?php _e("Refund Type:", "refund-tracker"); ?> <span class="required">*</span></label>
                        <select id="refund-type" required>
                            <option value=""><?php _e("Select type", "refund-tracker"); ?></option>
                            <option value="main"><?php _e("Main Refund", "refund-tracker"); ?></option>
                            <option value="recurring"><?php _e("Recurring Refund", "refund-tracker"); ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="refund-amount"><?php _e("Amount:", "refund-tracker"); ?> <span class="required">*</span></label>
                        <div class="amount-input">
                            <span class="currency-symbol">$</span>
                            <input type="number" id="refund-amount" step="0.01" min="0.01" required placeholder="0.00">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="refund-status"><?php _e("Status:", "refund-tracker"); ?> <span class="required">*</span></label>
                        <select id="refund-status" required>
                            <option value=""><?php _e("Select status", "refund-tracker"); ?></option>
                            <option value="refunded"><?php _e("Refunded", "refund-tracker"); ?></option>
                            <option value="not_refunded"><?php _e("Not Refunded", "refund-tracker"); ?></option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="button button-primary"><?php _e("Add Refund", "refund-tracker"); ?></button>
                    </div>
                </form>
                <div id="form-messages" class="hidden"></div>
            </div>
        </div>
    </div>
</div>;