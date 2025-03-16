/**
 * Refund Tracker Frontend JavaScript
 */
jQuery(document).ready(function($) {
    
    // Initialize datepickers
    $('.datepicker').datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true
    });
    
    // Tab functionality
    $('.refund-tracker-tabs .nav-tabs a').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        
        // Update active tab
        $('.refund-tracker-tabs .nav-tabs li').removeClass('active');
        $(this).parent('li').addClass('active');
        
        // Show target content
        $('.refund-tracker-tabs .tab-pane').removeClass('active');
        $(target).addClass('active');
    });
    
    // Load refunds on page load
    loadRefunds();
    
    // Apply filters button
    $('#apply-filters').on('click', function() {
        loadRefunds();
    });
    
    // Reset filters button
    $('#reset-filters').on('click', function() {
        $('#date-filter-start, #date-filter-end, #email-filter').val('');
        $('#type-filter, #status-filter').val('all');
        loadRefunds();
    });
    
    // Table header sorting
    $('.refund-tracker-table th[data-sort]').on('click', function() {
        var sortBy = $(this).data('sort');
        var currentOrder = $(this).hasClass('sort-asc') ? 'desc' : 'asc';
        
        // Update UI
        $('.refund-tracker-table th').removeClass('sort-asc sort-desc');
        $(this).addClass('sort-' + currentOrder);
        
        // Load with sorting
        loadRefunds(sortBy, currentOrder);
    });
    
    // Add refund form submission
    $('#add-refund-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            'action': 'add_refund',
            'nonce': refund_tracker_params.nonce,
            'date': $('#refund-date').val(),
            'email': $('#refund-email').val(),
            'refund_type': $('#refund-type').val(),
            'amount': $('#refund-amount').val(),
            'status': $('#refund-status').val()
        };
        
        $.ajax({
            type: 'POST',
            url: refund_tracker_params.ajax_url,
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $('#form-messages')
                        .removeClass('hidden error')
                        .addClass('success')
                        .text(response.data.message);
                    
                    // Reset form
                    $('#add-refund-form')[0].reset();
                    
                    // Switch to table tab and reload data after delay
                    setTimeout(function() {
                        $('.refund-tracker-tabs .nav-tabs li').removeClass('active');
                        $('.refund-tracker-tabs .nav-tabs li:first-child').addClass('active');
                        $('.refund-tracker-tabs .tab-pane').removeClass('active');
                        $('#refund-table').addClass('active');
                        loadRefunds();
                    }, 1500);
                } else {
                    // Show error message
                    $('#form-messages')
                        .removeClass('hidden success')
                        .addClass('error')
                        .text(response.data.message);
                }
            },
            error: function() {
                $('#form-messages')
                    .removeClass('hidden success')
                    .addClass('error')
                    .text('An error occurred. Please try again.');
            }
        });
    });
    
    // Function to load refunds with filters
    function loadRefunds(orderby, order) {
        var data = {
            'action': 'get_refunds',
            'nonce': refund_tracker_params.nonce,
            'start_date': $('#date-filter-start').val(),
            'end_date': $('#date-filter-end').val(),
            'email': $('#email-filter').val(),
            'refund_type': $('#type-filter').val(),
            'status': $('#status-filter').val()
        };
        
        // Add sorting parameters if provided
        if (orderby) {
            data.orderby = orderby;
        }
        
        if (order) {
            data.order = order;
        }
        
        $.ajax({
            type: 'GET',
            url: refund_tracker_params.ajax_url,
            data: data,
            beforeSend: function() {
                $('#refund-table-data tbody').html('<tr><td colspan="6" class="loading">Loading...</td></tr>');
            },
            success: function(response) {
                if (response.success) {
                    updateRefundTable(response.data.refunds);
                    updateRefundTotals(response.data.totals);
                } else {
                    $('#refund-table-data tbody').html('<tr><td colspan="6">' + response.data.message + '</td></tr>');
                }
            },
            error: function() {
                $('#refund-table-data tbody').html('<tr><td colspan="6">Error loading refunds. Please try again.</td></tr>');
            }
        });
    }
    
    // Function to update refund table with data
    function updateRefundTable(refunds) {
        var tableBody = $('#refund-table-data tbody');
        tableBody.empty();
        
        if (refunds.length === 0) {
            $('#no-refunds-message').removeClass('hidden');
            return;
        }
        
        $('#no-refunds-message').addClass('hidden');
        
        $.each(refunds, function(index, refund) {
            var row = $('<tr></tr>');
            
            // Format the date
            var date = new Date(refund.date);
            var formattedDate = date.toLocaleDateString();
            
            // Create the table cells
            row.append('<td>' + formattedDate + '</td>');
            row.append('<td>' + refund.email + '</td>');
            row.append('<td>' + (refund.refund_type === 'main' ? 'Main' : 'Recurring') + '</td>');
            row.append('<td>$' + parseFloat(refund.amount).toFixed(2) + '</td>');
            
            // Status with badge
            var statusText = refund.status === 'refunded' ? 'Refunded' : 'Not Refunded';
            var statusClass = 'status-' + refund.status;
            row.append('<td><span class="status-badge ' + statusClass + '">' + statusText + '</span></td>');
            
            // Actions
            var actions = '<td>';
            if (refund.status === 'not_refunded') {
                actions += '<button class="action-button mark-refunded" data-id="' + refund.id + '">Mark Refunded</button>';
            } else {
                actions += '<button class="action-button mark-not-refunded" data-id="' + refund.id + '">Mark Not Refunded</button>';
            }
            actions += '</td>';
            
            row.append(actions);
            tableBody.append(row);
        });
        
        // Initialize action buttons
        initActionButtons();
    }
    
    // Function to update refund totals
    function updateRefundTotals(totals) {
        $('#main-refund-total').text(parseFloat(totals.main_refund_total).toFixed(2));
        $('#recurring-refund-total').text(parseFloat(totals.recurring_refund_total).toFixed(2));
        $('#total-refund-amount').text(parseFloat(totals.total_refund).toFixed(2));
        $('#not-refunded-total').text(parseFloat(totals.not_refunded_total).toFixed(2));
    }
    
    // Function to initialize action buttons
    function initActionButtons() {
        // Mark as refunded
        $('.mark-refunded').on('click', function() {
            var refundId = $(this).data('id');
            updateRefundStatus(refundId, 'refunded');
        });
        
        // Mark as not refunded
        $('.mark-not-refunded').on('click', function() {
            var refundId = $(this).data('id');
            updateRefundStatus(refundId, 'not_refunded');
        });
    }
    
    // Function to update refund status
    function updateRefundStatus(refundId, status) {
        $.ajax({
            type: 'POST',
            url: refund_tracker_params.ajax_url,
            data: {
                'action': 'update_refund_status',
                'nonce': refund_tracker_params.nonce,
                'refund_id': refundId,
                'status': status
            },
            success: function(response) {
                if (response.success) {
                    // Reload the table to reflect changes
                    loadRefunds();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    }
});