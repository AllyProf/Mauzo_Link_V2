@extends('layouts.dashboard')

@section('title', 'Performance Reviews')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-star"></i> Performance Reviews</h1>
    <p>Manage staff performance reviews</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('hr.dashboard') }}">HR</a></li>
    <li class="breadcrumb-item">Performance Reviews</li>
  </ul>
</div>

<!-- Performance Reviews Table -->
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="tile-title-w-btn">
        <h3 class="title">Performance Reviews</h3>
        <button class="btn btn-primary" id="create-review-btn">
          <i class="fa fa-plus"></i> Create Review
        </button>
      </div>
      <div class="tile-body">
        @if($reviews->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Staff</th>
                  <th>Review Period</th>
                  <th>Review Date</th>
                  <th>Rating</th>
                  <th>Reviewer</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($reviews as $review)
                <tr>
                  <td>{{ $review->staff->full_name }}</td>
                  <td>{{ $review->review_period_start->format('M d') }} - {{ $review->review_period_end->format('M d, Y') }}</td>
                  <td>{{ $review->review_date->format('M d, Y') }}</td>
                  <td>
                    <span class="badge badge-{{ $review->performance_rating >= 4 ? 'success' : ($review->performance_rating >= 3 ? 'warning' : 'danger') }}">
                      {{ $review->performance_rating }}/5.0 - {{ $review->rating_label }}
                    </span>
                  </td>
                  <td>{{ $review->reviewer ? $review->reviewer->full_name : 'N/A' }}</td>
                  <td>
                    @if($review->status === 'completed')
                      <span class="badge badge-success">Completed</span>
                    @elseif($review->status === 'acknowledged')
                      <span class="badge badge-info">Acknowledged</span>
                    @else
                      <span class="badge badge-secondary">Draft</span>
                    @endif
                  </td>
                  <td>
                    <button class="btn btn-sm btn-info view-review-btn" data-review-id="{{ $review->id }}">
                      <i class="fa fa-eye"></i> View
                    </button>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="mt-3">
            {{ $reviews->links() }}
          </div>
        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No performance reviews found.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

