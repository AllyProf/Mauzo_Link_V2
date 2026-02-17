@extends('layouts.dashboard')

@section('title', 'Customer Database')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-users"></i> Customer Database</h1>
    <p>Manage and segment your customers</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('marketing.dashboard') }}">Marketing</a></li>
    <li class="breadcrumb-item">Customers</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="tile-title-w-btn">
        <h3 class="title">All Customers</h3>
        <div class="btn-group">
          <a href="{{ route('marketing.campaigns.create') }}" class="btn btn-primary">
            <i class="fa fa-plus"></i> Create Campaign
          </a>
        </div>
      </div>
      <div class="tile-body">
        <!-- Filters -->
        <form method="GET" action="{{ route('marketing.customers') }}" class="mb-4">
          <div class="row">
            <div class="col-md-4">
              <input type="text" name="search" class="form-control" placeholder="Search by name or phone..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
              <select name="segment" class="form-control">
                <option value="">All Customers</option>
                <option value="vip" {{ request('segment') === 'vip' ? 'selected' : '' }}>VIP Customers</option>
                <option value="active" {{ request('segment') === 'active' ? 'selected' : '' }}>Active (Last 30 days)</option>
                <option value="inactive" {{ request('segment') === 'inactive' ? 'selected' : '' }}>Inactive (60+ days)</option>
                <option value="new" {{ request('segment') === 'new' ? 'selected' : '' }}>New Customers</option>
              </select>
            </div>
            <div class="col-md-2">
              <button type="submit" class="btn btn-primary btn-block">
                <i class="fa fa-search"></i> Filter
              </button>
            </div>
            <div class="col-md-3 text-right">
              <a href="{{ route('marketing.customers') }}?export=csv" class="btn btn-success">
                <i class="fa fa-download"></i> Export CSV
              </a>
            </div>
          </div>
        </form>

        @if($customers->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover table-bordered">
              <thead>
                <tr>
                  <th>
                    <input type="checkbox" id="select-all">
                  </th>
                  <th>Customer Name</th>
                  <th>Phone Number</th>
                  <th>Total Orders</th>
                  <th>Total Spent</th>
                  <th>Last Order</th>
                  <th>First Order</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($customers as $customer)
                  <tr>
                    <td>
                      <input type="checkbox" class="customer-checkbox" value="{{ $customer->customer_phone }}" data-name="{{ $customer->customer_name }}">
                    </td>
                    <td>{{ $customer->customer_name ?? 'N/A' }}</td>
                    <td><strong>{{ $customer->customer_phone }}</strong></td>
                    <td>{{ number_format($customer->total_orders) }}</td>
                    <td><strong>TSh {{ number_format($customer->total_spent, 2) }}</strong></td>
                    <td>{{ $customer->last_order_date ? \Carbon\Carbon::parse($customer->last_order_date)->format('M d, Y') : 'N/A' }}</td>
                    <td>{{ $customer->first_order_date ? \Carbon\Carbon::parse($customer->first_order_date)->format('M d, Y') : 'N/A' }}</td>
                    <td>
                      <button class="btn btn-sm btn-primary send-single-sms" data-phone="{{ $customer->customer_phone }}" data-name="{{ $customer->customer_name }}">
                        <i class="fa fa-envelope"></i> Send SMS
                      </button>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="d-flex justify-content-center">
            {{ $customers->links() }}
          </div>
          
          <!-- Bulk Actions -->
          <div class="mt-3">
            <button class="btn btn-success" id="bulk-send-btn" disabled>
              <i class="fa fa-paper-plane"></i> Send SMS to Selected (0)
            </button>
            <span class="ml-2 text-muted" id="selected-count">0 customers selected</span>
          </div>
        @else
          <p class="text-muted">No customers found. Customers will appear here once waiters collect phone numbers during orders.</p>
        @endif
      </div>
    </div>
  </div>
</div>

<!-- Send SMS Modal -->
<div class="modal fade" id="sendSmsModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Send SMS</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <form id="sendSmsForm">
        <div class="modal-body">
          <div class="form-group">
            <label>Recipient(s)</label>
            <input type="text" class="form-control" id="recipient-info" readonly>
            <input type="hidden" id="recipient-phones">
          </div>
          <div class="form-group">
            <label>Message <span class="text-danger">*</span></label>
            <textarea class="form-control" id="sms-message" rows="5" required maxlength="1600"></textarea>
            <small class="text-muted">
              <span id="char-count">0</span> / 1600 characters
              <span id="sms-count">(1 SMS)</span>
            </small>
          </div>
          <div class="form-group">
            <button type="button" class="btn btn-sm btn-info" id="use-template-btn">
              <i class="fa fa-file-text"></i> Use Template
            </button>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="fa fa-paper-plane"></i> Send SMS
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Template Selector Modal -->
<div class="modal fade" id="templateSelectorModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Select Template</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="templates-loading" class="text-center py-4">
          <i class="fa fa-spinner fa-spin fa-2x"></i>
          <p>Loading templates...</p>
        </div>
        <div id="templates-container" style="display: none;">
          <div class="form-group">
            <input type="text" class="form-control" id="template-search" placeholder="Search templates...">
          </div>
          <div id="templates-list" style="max-height: 400px; overflow-y: auto;">
            <!-- Templates will be loaded here -->
          </div>
        </div>
        <div id="templates-error" style="display: none;" class="alert alert-danger">
          Failed to load templates. Please try again.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
  $(document).ready(function() {
    // Select all checkbox
    $('#select-all').on('change', function() {
      $('.customer-checkbox').prop('checked', $(this).prop('checked'));
      updateSelectedCount();
    });

    // Individual checkbox
    $(document).on('change', '.customer-checkbox', function() {
      updateSelectedCount();
    });

    function updateSelectedCount() {
      const selected = $('.customer-checkbox:checked');
      const count = selected.length;
      $('#selected-count').text(count + ' customer(s) selected');
      $('#bulk-send-btn').prop('disabled', count === 0).text('Send SMS to Selected (' + count + ')');
    }

    // Single SMS
    $(document).on('click', '.send-single-sms', function() {
      const phone = $(this).data('phone');
      const name = $(this).data('name');
      $('#recipient-info').val(name + ' - ' + phone);
      $('#recipient-phones').val(JSON.stringify([phone])); // Store as JSON array
      $('#sendSmsModal').modal('show');
    });

    // Bulk SMS
    $('#bulk-send-btn').on('click', function() {
      const selected = $('.customer-checkbox:checked');
      const phones = [];
      const names = [];
      selected.each(function() {
        phones.push($(this).val());
        names.push($(this).data('name') || 'Customer');
      });
      $('#recipient-info').val(phones.length + ' customer(s) selected');
      $('#recipient-phones').val(JSON.stringify(phones));
      $('#sendSmsModal').modal('show');
    });

    // Character count
    $('#sms-message').on('input', function() {
      const length = $(this).val().length;
      $('#char-count').text(length);
      const smsCount = Math.ceil(length / 160);
      $('#sms-count').text('(' + smsCount + ' SMS)');
    });

    // Template selector modal
    let allTemplates = [];
    
    // Open template selector from SMS modal
    $('#use-template-btn').on('click', function() {
      $('#templateSelectorModal').modal('show');
      loadTemplates();
    });
    
    $('#templateSelectorModal').on('show.bs.modal', function() {
      if (!$('#sendSmsModal').hasClass('show')) {
        // If SMS modal is not open, open it first
        $('#sendSmsModal').modal('show');
      }
    });

    function loadTemplates() {
      $('#templates-loading').show();
      $('#templates-container').hide();
      $('#templates-error').hide();
      
      $.ajax({
        url: '{{ route("marketing.templates.json") }}',
        method: 'GET',
        success: function(response) {
          if (response.success && response.templates) {
            allTemplates = response.templates;
            displayTemplates(allTemplates);
            $('#templates-loading').hide();
            $('#templates-container').show();
          } else {
            showTemplatesError();
          }
        },
        error: function() {
          showTemplatesError();
        }
      });
    }

    function showTemplatesError() {
      $('#templates-loading').hide();
      $('#templates-container').hide();
      $('#templates-error').show();
    }

    function displayTemplates(templates) {
      const container = $('#templates-list');
      container.empty();
      
      if (templates.length === 0) {
        container.html('<p class="text-muted text-center py-4">No templates found. <a href="{{ route("marketing.templates") }}">Create a template</a></p>');
        return;
      }
      
      templates.forEach(function(template, index) {
        const templateId = 'template-' + index;
        const templateCard = $('<div>')
          .addClass('card mb-2 template-item')
          .attr('data-name', template.name.toLowerCase())
          .attr('data-category', template.category)
          .html(`
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                  <h6 class="card-title mb-1">${escapeHtml(template.name)}</h6>
                  <p class="card-text text-muted small mb-2" style="white-space: pre-wrap; max-height: 100px; overflow: hidden;">${escapeHtml(template.content)}</p>
                  <small class="text-muted">
                    <span class="badge badge-primary">${template.category}</span>
                  </small>
                </div>
                <button class="btn btn-sm btn-primary ml-2 use-template-select-btn" data-template-index="${index}">
                  <i class="fa fa-check"></i> Use
                </button>
              </div>
            </div>
          `);
        container.append(templateCard);
      });
    }

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    // Template search
    $('#template-search').on('input', function() {
      const searchTerm = $(this).val().toLowerCase();
      $('.template-item').each(function() {
        const name = $(this).data('name');
        const content = $(this).find('.card-text').text().toLowerCase();
        if (name.includes(searchTerm) || content.includes(searchTerm)) {
          $(this).show();
        } else {
          $(this).hide();
        }
      });
    });

    // Use selected template
    $(document).on('click', '.use-template-select-btn', function() {
      const templateIndex = $(this).data('template-index');
      if (allTemplates[templateIndex]) {
        const content = allTemplates[templateIndex].content;
        $('#sms-message').val(content);
        // Trigger input event to update character count
        $('#sms-message').trigger('input');
        // Close template modal
        $('#templateSelectorModal').modal('hide');
        // Ensure SMS modal is still open (it should be in the background)
        setTimeout(function() {
          if (!$('#sendSmsModal').hasClass('show')) {
            $('#sendSmsModal').modal('show');
          }
        }, 300);
      }
    });

    // Send SMS form
    $('#sendSmsForm').on('submit', function(e) {
      e.preventDefault();
      let phones = [];
      const phonesValue = $('#recipient-phones').val();
      
      // Try to parse as JSON, if it fails, treat as single phone string
      try {
        phones = JSON.parse(phonesValue || '[]');
      } catch (e) {
        // If not JSON, treat as single phone number
        if (phonesValue && phonesValue.trim()) {
          phones = [phonesValue.trim()];
        }
      }
      
      const message = $('#sms-message').val();
      
      if (!message.trim()) {
        alert('Please enter a message');
        return;
      }
      
      if (phones.length === 0) {
        alert('Please select at least one recipient');
        return;
      }

      // Disable submit button and show loading
      const submitBtn = $(this).find('button[type="submit"]');
      const originalText = submitBtn.html();
      submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Sending...');

      // Send SMS via AJAX
      $.ajax({
        url: '{{ route("marketing.send-sms") }}',
        method: 'POST',
        data: {
          _token: '{{ csrf_token() }}',
          phones: phones,
          message: message
        },
        success: function(response) {
          if (response.success) {
            alert(response.message || 'SMS sent successfully!');
            $('#sendSmsModal').modal('hide');
            $('#sms-message').val('');
            $('#char-count').text('0');
            $('#sms-count').text('(1 SMS)');
          } else {
            alert('Error: ' + (response.message || 'Failed to send SMS'));
            if (response.errors && response.errors.length > 0) {
              console.error('SMS Errors:', response.errors);
            }
          }
        },
        error: function(xhr) {
          const errorMsg = xhr.responseJSON?.error || xhr.responseJSON?.message || 'Failed to send SMS. Please try again.';
          alert('Error: ' + errorMsg);
        },
        complete: function() {
          submitBtn.prop('disabled', false).html(originalText);
        }
      });
    });
  });
</script>
@endpush
@endsection

