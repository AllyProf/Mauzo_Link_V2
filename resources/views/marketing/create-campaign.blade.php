@extends('layouts.dashboard')

@section('title', 'Create Campaign')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-plus-circle"></i> Create SMS Campaign</h1>
    <p>Compose and send bulk SMS to your customers</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('marketing.dashboard') }}">Marketing</a></li>
    <li class="breadcrumb-item">Create Campaign</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Campaign Details</h3>
      <div class="tile-body">
        <form id="campaignForm">
          @csrf
          
          <!-- Campaign Name -->
          <div class="form-group">
            <label>Campaign Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" id="campaign-name" required placeholder="e.g., New Year Promotion 2025">
          </div>

          <!-- Message Type -->
          <div class="form-group">
            <label>Message Type</label>
            <select class="form-control" name="type" id="message-type">
              <option value="custom">Custom Message</option>
              <option value="template">Use Template</option>
            </select>
          </div>

          <!-- Template Selection (hidden by default) -->
          <div class="form-group" id="template-group" style="display: none;">
            <label>Select Template</label>
            <select class="form-control" name="template_id" id="template-select">
              <option value="">Choose a template...</option>
              @foreach($templates as $template)
                <option value="{{ $template->id }}" data-content="{{ $template->content }}" data-category="{{ $template->category }}">
                  {{ $template->name }} ({{ ucfirst($template->category) }})
                </option>
              @endforeach
            </select>
            <button type="button" class="btn btn-sm btn-info mt-2" id="preview-template-btn">
              <i class="fa fa-eye"></i> Preview Template
            </button>
          </div>

          <!-- Message Content -->
          <div class="form-group">
            <label>Message Content <span class="text-danger">*</span></label>
            <textarea class="form-control" name="message" id="message-content" rows="8" required maxlength="1600" placeholder="Enter your message here..."></textarea>
            <small class="text-muted">
              <span id="char-count">0</span> / 1600 characters
              <span id="sms-count">(1 SMS)</span>
            </small>
            <div class="mt-2">
              <small class="text-info">
                <strong>Available placeholders:</strong> {customer_name}, {total_orders}, {total_spent}, {last_order_date}, {business_name}
              </small>
            </div>
          </div>

          <!-- Recipients -->
          <div class="form-group">
            <label>Recipients <span class="text-danger">*</span></label>
            <select class="form-control" name="recipient_type" id="recipient-type" required>
              <option value="all">All Customers ({{ number_format($totalCustomers) }})</option>
              <option value="selected">Selected Customers</option>
              <option value="segment">Customer Segment</option>
            </select>
          </div>

          <!-- Selected Customers (hidden by default) -->
          <div class="form-group" id="selected-customers-group" style="display: none;">
            <label>Select Customers</label>
            <button type="button" class="btn btn-sm btn-primary mb-2" id="choose-customers-btn">
              <i class="fa fa-users"></i> Choose from Customer Database
            </button>
            <div id="selected-customers-display" class="mt-2 mb-2" style="display: none;">
              <div class="alert alert-info">
                <strong>Selected:</strong> <span id="selected-count-display">0</span> customer(s)
                <button type="button" class="btn btn-sm btn-link p-0 ml-2" id="clear-selected-customers">Clear</button>
              </div>
            </div>
            <input type="hidden" name="selected_customers" id="selected-customers" value="">
            <input type="text" class="form-control" id="selected-customers-manual" placeholder="Or enter phone numbers manually (comma-separated)">
            <small class="text-muted">You can select from database or enter phone numbers manually</small>
          </div>

          <!-- Segment Selection (hidden by default) -->
          <div class="form-group" id="segment-group" style="display: none;">
            <label>Select Segment</label>
            <select class="form-control" name="segment_id" id="segment-select">
              <option value="">Choose a segment...</option>
              @foreach($segments as $segment)
                <option value="{{ $segment->id }}">{{ $segment->name }} ({{ $segment->customer_count }} customers)</option>
              @endforeach
            </select>
          </div>

          <!-- Schedule -->
          <div class="form-group">
            <label>
              <input type="checkbox" id="schedule-campaign" name="schedule"> Schedule for later
            </label>
          </div>

          <div class="form-group" id="schedule-date-group" style="display: none;">
            <label>Schedule Date & Time</label>
            <input type="datetime-local" class="form-control" name="scheduled_at" id="scheduled-at" min="{{ date('Y-m-d\TH:i') }}">
          </div>

          <!-- Preview -->
          <div class="form-group">
            <button type="button" class="btn btn-info" id="preview-btn">
              <i class="fa fa-eye"></i> Preview Message
            </button>
          </div>

          <!-- Submit Buttons -->
          <div class="form-group">
            <button type="submit" class="btn btn-primary btn-lg" id="submit-btn">
              <i class="fa fa-paper-plane"></i> Send Campaign
            </button>
            <button type="button" class="btn btn-secondary btn-lg" id="save-draft-btn">
              <i class="fa fa-save"></i> Save as Draft
            </button>
            <a href="{{ route('marketing.campaigns') }}" class="btn btn-default btn-lg">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Message Preview</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="card" style="max-width: 300px; margin: 0 auto;">
          <div class="card-body">
            <p class="card-text" id="preview-message" style="white-space: pre-wrap;"></p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Customer Selection Modal -->
<div class="modal fade" id="customerSelectionModal" tabindex="-1" role="dialog" style="z-index: 1050;">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Select Customers</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <input type="text" class="form-control" id="customer-search" placeholder="Search by name or phone...">
        </div>
        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
          <table class="table table-hover table-sm">
            <thead class="thead-light" style="position: sticky; top: 0; z-index: 10;">
              <tr>
                <th width="50">
                  <input type="checkbox" id="select-all-customers">
                </th>
                <th>Name</th>
                <th>Phone</th>
                <th>Orders</th>
                <th>Total Spent</th>
              </tr>
            </thead>
            <tbody id="customers-table-body">
              <tr>
                <td colspan="5" class="text-center">
                  <i class="fa fa-spinner fa-spin"></i> Loading customers...
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="mt-3">
          <strong>Selected: <span id="modal-selected-count">0</span> customer(s)</strong>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="confirm-customer-selection">Add Selected Customers</button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  $(document).ready(function() {
    // Get pre-filled data from URL
    const urlParams = new URLSearchParams(window.location.search);
    const recipients = urlParams.get('recipients');
    const message = urlParams.get('message');
    
    if (recipients) {
      $('#recipient-type').val('selected');
      $('#selected-customers-group').show();
      $('#selected-customers').val(recipients);
    }
    if (message) {
      $('#message-content').val(decodeURIComponent(message));
      updateCharCount();
    }

    // Message type toggle
    $('#message-type').on('change', function() {
      if ($(this).val() === 'template') {
        $('#template-group').show();
      } else {
        $('#template-group').hide();
      }
    });

    // Template selection
    $('#template-select').on('change', function() {
      const content = $(this).find(':selected').data('content');
      if (content) {
        $('#message-content').val(content);
        updateCharCount();
      }
    });

    // Recipient type toggle
    $('#recipient-type').on('change', function() {
      $('#selected-customers-group').hide();
      $('#segment-group').hide();
      
      if ($(this).val() === 'selected') {
        $('#selected-customers-group').show();
      } else if ($(this).val() === 'segment') {
        $('#segment-group').show();
      }
    });

    // Customer selection modal
    let selectedCustomers = [];
    
    $('#choose-customers-btn').on('click', function() {
      $('#customerSelectionModal').modal('show');
      loadCustomers();
    });

    function loadCustomers(search = '') {
      $.ajax({
        url: '{{ route("marketing.customers") }}',
        method: 'GET',
        data: { search: search, format: 'json' },
        success: function(response) {
          if (response.customers && response.customers.length > 0) {
            let html = '';
            response.customers.forEach(function(customer) {
              const isSelected = selectedCustomers.includes(customer.customer_phone);
              html += '<tr>';
              html += '<td><input type="checkbox" class="customer-checkbox-modal" value="' + customer.customer_phone + '" data-name="' + (customer.customer_name || 'N/A') + '" ' + (isSelected ? 'checked' : '') + '></td>';
              html += '<td>' + (customer.customer_name || 'N/A') + '</td>';
              html += '<td><strong>' + customer.customer_phone + '</strong></td>';
              html += '<td>' + (customer.total_orders || 0) + '</td>';
              html += '<td>TSh ' + parseFloat(customer.total_spent || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>';
              html += '</tr>';
            });
            $('#customers-table-body').html(html);
          } else {
            $('#customers-table-body').html('<tr><td colspan="5" class="text-center">No customers found</td></tr>');
          }
          updateModalSelectedCount();
        },
        error: function() {
          $('#customers-table-body').html('<tr><td colspan="5" class="text-center text-danger">Error loading customers</td></tr>');
        }
      });
    }

    // Search customers
    $('#customer-search').on('keyup', function() {
      const search = $(this).val();
      clearTimeout(window.searchTimeout);
      window.searchTimeout = setTimeout(function() {
        loadCustomers(search);
      }, 500);
    });

    // Select all customers in modal
    $('#select-all-customers').on('change', function() {
      $('.customer-checkbox-modal').prop('checked', $(this).prop('checked'));
      updateModalSelectedCount();
    });

    // Individual customer checkbox
    $(document).on('change', '.customer-checkbox-modal', function() {
      updateModalSelectedCount();
    });

    function updateModalSelectedCount() {
      const selected = $('.customer-checkbox-modal:checked');
      $('#modal-selected-count').text(selected.length);
    }

    // Confirm customer selection
    $('#confirm-customer-selection').on('click', function() {
      const selected = $('.customer-checkbox-modal:checked');
      selectedCustomers = [];
      selected.each(function() {
        selectedCustomers.push($(this).val());
      });
      
      updateSelectedCustomersDisplay();
      $('#customerSelectionModal').modal('hide');
    });

    // Clear selected customers
    $('#clear-selected-customers').on('click', function() {
      selectedCustomers = [];
      $('#selected-customers-manual').val('');
      updateSelectedCustomersDisplay();
    });

    // Manual input for customers
    $('#selected-customers-manual').on('blur', function() {
      const manualPhones = $(this).val().split(',').map(p => p.trim()).filter(p => p);
      // Merge with selected customers, avoiding duplicates
      manualPhones.forEach(function(phone) {
        if (!selectedCustomers.includes(phone)) {
          selectedCustomers.push(phone);
        }
      });
      updateSelectedCustomersDisplay();
    });

    function updateSelectedCustomersDisplay() {
      const count = selectedCustomers.length;
      if (count > 0) {
        $('#selected-customers-display').show();
        $('#selected-count-display').text(count);
        $('#selected-customers').val(selectedCustomers.join(','));
      } else {
        $('#selected-customers-display').hide();
        $('#selected-customers').val('');
      }
    }

    // Schedule toggle
    $('#schedule-campaign').on('change', function() {
      if ($(this).prop('checked')) {
        $('#schedule-date-group').show();
      } else {
        $('#schedule-date-group').hide();
      }
    });

    // Character count
    $('#message-content').on('input', updateCharCount);
    function updateCharCount() {
      const length = $('#message-content').val().length;
      $('#char-count').text(length);
      const smsCount = Math.ceil(length / 160);
      $('#sms-count').text('(' + smsCount + ' SMS)');
    }

    // Preview
    $('#preview-btn').on('click', function() {
      const message = $('#message-content').val();
      if (!message.trim()) {
        alert('Please enter a message first');
        return;
      }
      $('#preview-message').text(message);
      $('#previewModal').modal('show');
    });

    // Preview template
    $('#preview-template-btn').on('click', function() {
      const content = $('#template-select').find(':selected').data('content');
      if (content) {
        $('#preview-message').text(content);
        $('#previewModal').modal('show');
      } else {
        alert('Please select a template first');
      }
    });

    // Form submission
    $('#campaignForm').on('submit', function(e) {
      e.preventDefault();
      submitCampaign(false);
    });

    $('#save-draft-btn').on('click', function() {
      submitCampaign(true);
    });

    function submitCampaign(isDraft) {
      const formData = new FormData($('#campaignForm')[0]);
      if (isDraft) {
        formData.append('save_draft', '1');
      }

      // Show loading
      $('#submit-btn, #save-draft-btn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');

      $.ajax({
        url: '{{ route("marketing.campaigns.store") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
          let message = response.message || 'Campaign created successfully!';
          let htmlMessage = message.replace(/\n/g, '<br>');
          
          // If SMS were sent, show detailed stats
          if (response.sent_count !== undefined) {
            htmlMessage = '<div style="text-align: left;">';
            htmlMessage += '<strong>Campaign Sent Successfully!</strong><br><br>';
            htmlMessage += '<strong>Total Recipients:</strong> ' + (response.total_recipients || 0) + '<br>';
            htmlMessage += '<strong style="color: #28a745;">Sent:</strong> ' + (response.sent_count || 0) + '<br>';
            if (response.failed_count > 0) {
              htmlMessage += '<strong style="color: #dc3545;">Failed:</strong> ' + response.failed_count + '<br>';
            }
            htmlMessage += '</div>';
          }
          
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            html: htmlMessage,
            confirmButtonText: 'View Campaign',
            confirmButtonColor: '#28a745',
            allowOutsideClick: false
          }).then((result) => {
            if (result.isConfirmed) {
              window.location.href = '{{ route("marketing.campaigns") }}';
            }
          });
        },
        error: function(xhr) {
          let error = 'An error occurred';
          
          if (xhr.responseJSON) {
            if (xhr.responseJSON.error) {
              error = xhr.responseJSON.error;
            } else if (xhr.responseJSON.message) {
              error = xhr.responseJSON.message;
            } else if (xhr.responseJSON.errors) {
              // Validation errors
              const errors = xhr.responseJSON.errors;
              error = Object.values(errors).flat().join('\n');
            }
          } else if (xhr.responseText) {
            try {
              const parsed = JSON.parse(xhr.responseText);
              error = parsed.error || parsed.message || error;
            } catch (e) {
              error = xhr.responseText.substring(0, 200);
            }
          } else {
            error = xhr.statusText || 'Unknown error';
          }
          
          console.error('Campaign creation error:', xhr);
          
          Swal.fire({
            icon: 'error',
            title: 'Error',
            html: '<div style="text-align: left;">' + error.replace(/\n/g, '<br>') + '</div>',
            confirmButtonColor: '#940000'
          });
          $('#submit-btn, #save-draft-btn').prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Send Campaign');
        }
      });
    }
  });
</script>
@endpush
@endsection

