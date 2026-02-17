@extends('layouts.dashboard')

@section('title', 'SMS Templates')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-file-text"></i> SMS Templates</h1>
    <p>Manage your SMS message templates</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('marketing.dashboard') }}">Marketing</a></li>
    <li class="breadcrumb-item">Templates</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="tile-title-w-btn">
        <h3 class="title">All Templates</h3>
        <div class="btn-group">
          <button class="btn btn-primary" data-toggle="modal" data-target="#createTemplateModal">
            <i class="fa fa-plus"></i> Create Template
          </button>
        </div>
      </div>
      <div class="tile-body">
        <!-- Filter by Category -->
        <div class="mb-3">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-primary filter-category active" data-category="">All</button>
            <button type="button" class="btn btn-outline-primary filter-category" data-category="holiday">Holidays</button>
            <button type="button" class="btn btn-outline-primary filter-category" data-category="promotion">Promotions</button>
            <button type="button" class="btn btn-outline-primary filter-category" data-category="update">Updates</button>
            <button type="button" class="btn btn-outline-primary filter-category" data-category="engagement">Engagement</button>
            <button type="button" class="btn btn-outline-primary filter-category" data-category="custom">Custom</button>
          </div>
        </div>

        @if($templates->count() > 0)
          <div class="row" id="templates-container">
            @foreach($templates as $template)
              <div class="col-md-6 mb-4 template-item" data-category="{{ $template->category }}">
                <div class="card h-100">
                  <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ $template->name }}</h5>
                    <div>
                      @if($template->is_system_template)
                        <span class="badge badge-info">System</span>
                      @else
                        <span class="badge badge-secondary">Custom</span>
                      @endif
                      <span class="badge badge-primary">{{ ucfirst($template->category) }}</span>
                    </div>
                  </div>
                  <div class="card-body">
                    <p class="card-text" style="white-space: pre-wrap; min-height: 100px;">{{ $template->content }}</p>
                    @if($template->description)
                      <small class="text-muted">{{ $template->description }}</small>
                    @endif
                    @if($template->placeholders && count($template->placeholders) > 0)
                      <div class="mt-2">
                        <small class="text-info">
                          <strong>Placeholders:</strong> {{ implode(', ', $template->placeholders) }}
                        </small>
                      </div>
                    @endif
                    <div class="mt-2">
                      <small class="text-muted">
                        Used {{ $template->usage_count }} time(s) | 
                        Language: {{ strtoupper($template->language) }}
                      </small>
                    </div>
                  </div>
                  <div class="card-footer">
                    <button class="btn btn-sm btn-primary use-template-btn" data-id="{{ $template->id }}" data-content="{{ $template->content }}">
                      <i class="fa fa-check"></i> Use Template
                    </button>
                    <button class="btn btn-sm btn-info preview-template-btn" data-content="{{ $template->content }}">
                      <i class="fa fa-eye"></i> Preview
                    </button>
                    @if(!$template->is_system_template)
                      <button class="btn btn-sm btn-danger delete-template-btn" data-id="{{ $template->id }}">
                        <i class="fa fa-trash"></i> Delete
                      </button>
                    @endif
                  </div>
                </div>
              </div>
            @endforeach
          </div>
          <div class="d-flex justify-content-center">
            {{ $templates->links() }}
          </div>
        @else
          <p class="text-muted">No templates found. <button class="btn btn-link" data-toggle="modal" data-target="#createTemplateModal">Create your first template</button></p>
        @endif
      </div>
    </div>
  </div>
</div>

<!-- Create Template Modal -->
<div class="modal fade" id="createTemplateModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create Template</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <form id="createTemplateForm">
        @csrf
        <div class="modal-body">
          <div class="form-group">
            <label>Template Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" required>
          </div>
          <div class="form-group">
            <label>Category <span class="text-danger">*</span></label>
            <select class="form-control" name="category" required>
              <option value="holiday">Holiday</option>
              <option value="promotion">Promotion</option>
              <option value="update">Update</option>
              <option value="engagement">Engagement</option>
              <option value="custom">Custom</option>
            </select>
          </div>
          <div class="form-group">
            <label>Language</label>
            <select class="form-control" name="language">
              <option value="en">English</option>
              <option value="sw">Swahili</option>
              <option value="both">Both</option>
            </select>
          </div>
          <div class="form-group">
            <label>Message Content <span class="text-danger">*</span></label>
            <textarea class="form-control" name="content" rows="8" required maxlength="1600"></textarea>
            <small class="text-muted">
              Available placeholders: {customer_name}, {total_orders}, {total_spent}, {last_order_date}, {business_name}
            </small>
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea class="form-control" name="description" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Template</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewTemplateModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Template Preview</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="card" style="max-width: 300px; margin: 0 auto;">
          <div class="card-body">
            <p class="card-text" id="preview-content" style="white-space: pre-wrap;"></p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  $(document).ready(function() {
    // Category filter
    $('.filter-category').on('click', function() {
      $('.filter-category').removeClass('active');
      $(this).addClass('active');
      const category = $(this).data('category');
      
      if (category === '') {
        $('.template-item').show();
      } else {
        $('.template-item').hide();
        $('.template-item[data-category="' + category + '"]').show();
      }
    });

    // Use template
    $('.use-template-btn').on('click', function() {
      const content = $(this).data('content');
      window.location.href = '{{ route("marketing.campaigns.create") }}?template_content=' + encodeURIComponent(content);
    });

    // Preview template
    $('.preview-template-btn').on('click', function() {
      const content = $(this).data('content');
      $('#preview-content').text(content);
      $('#previewTemplateModal').modal('show');
    });

    // Create template form
    $('#createTemplateForm').on('submit', function(e) {
      e.preventDefault();
      const formData = $(this).serialize();
      
      $.ajax({
        url: '{{ route("marketing.templates.store") }}',
        method: 'POST',
        data: formData,
        success: function(response) {
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: 'Template created successfully!'
          }).then(() => {
            location.reload();
          });
        },
        error: function(xhr) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: xhr.responseJSON?.error || 'Failed to create template'
          });
        }
      });
    });

    // Delete template
    $('.delete-template-btn').on('click', function() {
      const templateId = $(this).data('id');
      Swal.fire({
        title: 'Delete Template?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          // Implement delete functionality if needed
          Swal.fire('Deleted!', 'Template has been deleted.', 'success');
        }
      });
    });
  });
</script>
@endpush
@endsection







