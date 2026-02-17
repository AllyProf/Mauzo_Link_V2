@extends('layouts.dashboard')

@section('title', 'Waiter Orders')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-list-alt"></i> Waiter Orders</h1>
    <p>Manage orders from waiters</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Counter</li>
    <li class="breadcrumb-item">Waiter Orders</li>
  </ul>
</div>

<div class="row">
  <!-- Status Summary Cards -->
  <div class="col-md-3">
    <div class="widget-small primary coloured-icon">
      <i class="icon fa fa-clock-o fa-3x"></i>
      <div class="info">
        <h4>Pending</h4>
        <p><b>{{ $pendingCount }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small warning coloured-icon">
      <i class="icon fa fa-truck fa-3x"></i>
      <div class="info">
        <h4>Served</h4>
        <p><b>{{ $servedCount }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small success coloured-icon">
      <i class="icon fa fa-money fa-3x"></i>
      <div class="info">
        <h4>Total Orders</h4>
        <p><b>{{ $orders->total() }}</b></p>
      </div>
    </div>
  </div>
</div>

<!-- Audio Enable Banner -->
<div id="audio-enable-banner" class="alert alert-warning" style="display: none; margin-bottom: 20px;">
  <div class="row align-items-center">
    <div class="col-md-10">
      <strong><i class="fa fa-volume-up"></i> Audio Not Enabled</strong>
      <p class="mb-0">Click the button below to enable audio announcements for new orders.</p>
    </div>
    <div class="col-md-2 text-right">
      <button id="enable-audio-btn" class="btn btn-primary btn-lg">
        <i class="fa fa-volume-up"></i> Enable Audio
      </button>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="tile-title-w-btn">
        <h3 class="title">All Waiter Orders</h3>
        <div class="btn-group">
          <button class="btn btn-primary filter-btn" data-status="all">
            <i class="fa fa-list"></i> All
          </button>
          <button class="btn btn-outline-primary filter-btn" data-status="pending">
            <i class="fa fa-clock-o"></i> Pending
          </button>
          <button class="btn btn-outline-primary filter-btn" data-status="served">
            <i class="fa fa-truck"></i> Served
          </button>
        </div>
      </div>
      <div class="tile-body">
        <div class="table-responsive">
          <table class="table table-hover table-bordered" id="orders-table">
            <thead>
              <tr>
                <th>Order #</th>
                <th>Waiter</th>
                <th>Source</th>
                <th>Items</th>
                <th>Total</th>
                <th>Status</th>
                <th>Payment</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($orders as $order)
              <tr data-status="{{ $order->status }}" data-order-id="{{ $order->id }}">
                <td><strong>{{ $order->order_number }}</strong></td>
                <td>
                  @if($order->waiter)
                    <i class="fa fa-user"></i> {{ $order->waiter->full_name }}<br>
                    <small class="text-muted">{{ $order->waiter->staff_id }}</small>
                  @else
                    <span class="text-muted">N/A</span>
                  @endif
                </td>
                <td>
                  @if($order->order_source === 'kiosk')
                    <span class="badge badge-info"><i class="fa fa-desktop"></i> Kiosk</span>
                  @elseif($order->waiter_id)
                    <span class="badge badge-primary"><i class="fa fa-user"></i> Waiter</span>
                  @else
                    <span class="badge badge-secondary"><i class="fa fa-globe"></i> Web</span>
                  @endif
                </td>
                <td>
                  <ul class="list-unstyled mb-0">
                    @foreach($order->items->take(3) as $item)
                    <li>
                      <small>{{ $item->quantity }}x {{ $item->productVariant->product->name }}</small>
                    </li>
                    @endforeach
                    @if($order->items->count() > 3)
                    <li><small class="text-muted">+{{ $order->items->count() - 3 }} more</small></li>
                    @endif
                  </ul>
                </td>
                <td><strong>TSh {{ number_format($order->total_amount, 2) }}</strong></td>
                <td>
                  <span class="badge badge-{{ $order->status === 'pending' ? 'warning' : ($order->status === 'served' ? 'success' : 'secondary') }}">
                    {{ ucfirst($order->status) }}
                  </span>
                </td>
                <td>
                  @if($order->payment_status === 'paid')
                    <span class="badge badge-success">
                      <i class="fa fa-check"></i> Paid
                    </span>
                    @if($order->paidByWaiter)
                      <br><small class="text-muted">Paid by {{ $order->paidByWaiter->full_name }}</small>
                    @endif
                  @elseif($order->payment_status === 'partial')
                    <span class="badge badge-warning">
                      Partial: TSh {{ number_format($order->paid_amount, 2) }}
                    </span>
                    @if($order->paidByWaiter)
                      <br><small class="text-muted">Paid by {{ $order->paidByWaiter->full_name }}</small>
                    @endif
                  @elseif($order->orderPayments && $order->orderPayments->count() > 0 || $order->paid_by_waiter_id)
                    {{-- Payment has been recorded by waiter but not yet reconciled --}}
                    <span class="badge badge-info">
                      <i class="fa fa-check"></i> Paid
                    </span>
                    @if($order->paidByWaiter)
                      <br><small class="text-muted">Paid by {{ $order->paidByWaiter->full_name }}</small>
                    @elseif($order->orderPayments && $order->orderPayments->count() > 0)
                      <br><small class="text-muted">Paid by waiter</small>
                    @endif
                  @else
                    <span class="badge badge-danger">Pending</span>
                  @endif
                </td>
                <td>{{ $order->created_at->format('M d, Y H:i') }}</td>
                <td>
                  <div class="btn-group">
                    <button class="btn btn-sm btn-info view-order-btn" data-order-id="{{ $order->id }}">
                      <i class="fa fa-eye"></i> View
                    </button>
                    @if($order->status === 'pending')
                      <button class="btn btn-sm btn-primary update-status-btn" 
                              data-order-id="{{ $order->id }}" 
                              data-status="served">
                        <i class="fa fa-truck"></i> Mark Served
                      </button>
                    @endif
                    {{-- Payment marking is done in reconciliation after waiter submits daily collection --}}
                  </div>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="9" class="text-center">
                  <p class="text-muted">No orders found</p>
                </td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="mt-3">
          @if($orders->hasPages())
            <ul class="pagination justify-content-center">
              {{-- Previous Page Link --}}
              @if($orders->onFirstPage())
                <li class="page-item disabled">
                  <span class="page-link">«</span>
                </li>
              @else
                <li class="page-item">
                  <a class="page-link" href="{{ $orders->previousPageUrl() }}" rel="prev">«</a>
                </li>
              @endif

              {{-- Pagination Elements --}}
              @foreach($orders->getUrlRange(max(1, $orders->currentPage() - 2), min($orders->lastPage(), $orders->currentPage() + 2)) as $page => $url)
                @if($page == $orders->currentPage())
                  <li class="page-item active">
                    <span class="page-link">{{ $page }}</span>
                  </li>
                @else
                  <li class="page-item">
                    <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                  </li>
                @endif
              @endforeach

              {{-- Ellipsis for pages after current range --}}
              @if($orders->currentPage() + 2 < $orders->lastPage())
                @if($orders->currentPage() + 3 < $orders->lastPage())
                  <li class="page-item disabled"><span class="page-link">...</span></li>
                @endif
                <li class="page-item">
                  <a class="page-link" href="{{ $orders->url($orders->lastPage()) }}">{{ $orders->lastPage() }}</a>
                </li>
              @endif

              {{-- Next Page Link --}}
              @if($orders->hasMorePages())
                <li class="page-item">
                  <a class="page-link" href="{{ $orders->nextPageUrl() }}" rel="next">»</a>
                </li>
              @else
                <li class="page-item disabled">
                  <span class="page-link">»</span>
                </li>
              @endif
            </ul>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="order-details-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Order Details</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body" id="order-details-content">
        <!-- Order details will be loaded here -->
      </div>
    </div>
  </div>
</div>

<!-- Mark Paid Modal -->
<div class="modal fade" id="mark-paid-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Mark Order as Paid</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <form id="mark-paid-form">
        <div class="modal-body">
          <input type="hidden" id="paid-order-id" name="order_id">
          <div class="form-group">
            <label>Paid Amount</label>
            <input type="number" class="form-control" id="paid-amount" name="paid_amount" step="0.01" required>
            <small class="form-text text-muted">Total Amount: <span id="order-total-amount"></span></small>
          </div>
          <div class="form-group">
            <label>Waiter Who Collected Payment</label>
            <select class="form-control" id="paid-by-waiter" name="waiter_id" required>
              <option value="">Select Waiter</option>
              @foreach($waiters as $waiter)
              <option value="{{ $waiter->id }}">{{ $waiter->full_name }} ({{ $waiter->staff_id }})</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Mark as Paid</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // ============================================
  // Real-time Order Detection with Swahili TTS
  // ============================================
  
  // Debug mode - set to false in production
  const DEBUG_MODE = true; // Temporarily enabled for debugging TTS issues
  
  // Initialize state
  let lastOrderId = {{ $orders->count() > 0 ? $orders->first()->id : 0 }};
  let announcedOrders = new Set();
  let isPolling = true;
  let pollInterval = null;
  let speechSynthesis = window.speechSynthesis;
  let speechEnabled = false; // Will be enabled after user interaction
  let audioEnabled = false; // Audio playback enabled after user interaction
  let pollCount = 0;
  let errorCount = 0;
  
  // Debug logging
  function debugLog(message, data = null) {
    if (DEBUG_MODE) {
      const timestamp = new Date().toLocaleTimeString();
      console.log(`[${timestamp}] ${message}`, data || '');
    }
  }
  
  // Error logging
  function errorLog(message, error = null) {
    errorCount++;
    const timestamp = new Date().toLocaleTimeString();
    console.error(`[${timestamp}] ERROR: ${message}`, error || '');
    
    // Show error in UI if too many errors
    if (errorCount > 5) {
      showDebugMessage('Multiple errors detected. Check console.', 'error');
    }
  }
  
  // Show debug message on screen (only in console when DEBUG_MODE is true)
  function showDebugMessage(message, type = 'info') {
    // Only log to console, no visual panel
    if (DEBUG_MODE) {
      debugLog(message, type);
    }
  }

  // Check browser support for speech synthesis
  if (!speechSynthesis) {
    errorLog('Speech synthesis not supported in this browser');
    showDebugMessage('Speech synthesis not supported', 'error');
  } else {
    debugLog('Speech synthesis API available');
    showDebugMessage('Speech synthesis API available', 'success');
  }
  
  // Enable speech and audio after user interaction (browser security requirement)
  function enableSpeech() {
    if (speechSynthesis && !speechEnabled) {
      // Test speech with a silent utterance
      const testUtterance = new SpeechSynthesisUtterance('');
      testUtterance.volume = 0;
      speechSynthesis.speak(testUtterance);
      speechEnabled = true;
      debugLog('Speech enabled after user interaction');
    }
    
    // Enable audio playback - just mark as enabled after user interaction
    // The actual audio files will work once user has interacted with the page
    // (browser autoplay policy requires user interaction)
    if (!audioEnabled) {
      audioEnabled = true;
      debugLog('Audio playback enabled (user interaction detected)');
      
      // Test audio playback with a real audio file to unlock autoplay
      // This must happen during the user interaction event
      if (voiceClipsLoaded && voiceClips.static && Object.keys(voiceClips.static).length > 0) {
        // Get the first available audio clip URL
        const firstClipUrl = Object.values(voiceClips.static)[0];
        if (firstClipUrl) {
          const testAudio = new Audio(firstClipUrl);
          testAudio.volume = 0.01; // Very quiet, just to unlock autoplay
          testAudio.play().then(() => {
            debugLog('Audio autoplay unlocked successfully');
            testAudio.pause();
            testAudio.currentTime = 0;
          }).catch((error) => {
            debugLog('Audio autoplay test failed (will retry on actual playback):', error);
          });
        }
      }
      
      updateAudioUI();
    }
  }
  
  // Update UI after audio is enabled
  function updateAudioUI() {
    // Hide the banner
    const banner = document.getElementById('audio-enable-banner');
    if (banner) {
      banner.style.display = 'none';
    }
    // Update button
    const btn = document.getElementById('enable-audio-btn');
    if (btn) {
      btn.innerHTML = '<i class="fa fa-check"></i> Audio Enabled';
      btn.classList.remove('btn-primary');
      btn.classList.add('btn-success');
      btn.disabled = true;
    }
    
    // Play any queued announcements immediately (as part of user interaction)
    if (announcementQueue.length > 0) {
      debugLog(`Playing ${announcementQueue.length} queued announcement(s) immediately`);
      const queuedOrders = [...announcementQueue];
      announcementQueue = []; // Clear queue
      
      // Play the first announcement immediately (as part of user interaction)
      if (queuedOrders.length > 0) {
        const firstOrder = queuedOrders[0];
        debugLog(`Playing first queued announcement immediately: ${firstOrder.order_number}`);
        // Play immediately - this is still part of the user interaction
        playAnnouncementWithAudio(firstOrder);
        
        // Play remaining announcements with delays
        if (queuedOrders.length > 1) {
          queuedOrders.slice(1).forEach((order, index) => {
            setTimeout(() => {
              debugLog(`Playing queued announcement: ${order.order_number}`);
              playAnnouncementWithAudio(order);
            }, (index + 1) * 3000); // 3 second delay between announcements
          });
        }
      }
    }
  }
  
  // Show banner if audio not enabled
  function checkAudioStatus() {
    if (!audioEnabled || !speechEnabled) {
      const banner = document.getElementById('audio-enable-banner');
      if (banner) {
        banner.style.display = 'block';
      }
    }
  }
  
  // Enable audio button click handler
  document.addEventListener('DOMContentLoaded', function() {
    const enableBtn = document.getElementById('enable-audio-btn');
    if (enableBtn) {
      enableBtn.addEventListener('click', function() {
        enableSpeech();
        // Also enable on any click anywhere
        document.addEventListener('click', enableSpeech, { once: true });
        document.addEventListener('keydown', enableSpeech, { once: true });
        document.addEventListener('touchstart', enableSpeech, { once: true });
      });
    }
    
    // Check status on load
    checkAudioStatus();
  });
  
  // Enable speech on any user interaction (as fallback)
  ['click', 'keydown', 'touchstart'].forEach(event => {
    document.addEventListener(event, enableSpeech, { once: true });
  });

  /**
   * Get Swahili voice (wait for voices to load if needed)
   */
  function getSwahiliVoice() {
    const voices = speechSynthesis.getVoices();
    
    // Try multiple Swahili language codes and name patterns
    const swahiliPatterns = [
      { lang: 'sw-TZ', name: 'Swahili' }, // Tanzania Swahili
      { lang: 'sw-KE', name: 'Swahili' }, // Kenya Swahili
      { lang: 'sw', name: 'Swahili' },    // Generic Swahili
    ];
    
    // First, try to find exact Swahili voice
    for (const pattern of swahiliPatterns) {
      const voice = voices.find(v => 
        v.lang === pattern.lang || 
        (v.lang.startsWith('sw') && v.name.toLowerCase().includes('swahili'))
      );
      if (voice) {
        debugLog(`Found Swahili voice: ${voice.name} (${voice.lang})`);
        return voice;
      }
    }
    
    // Try broader search
    const swahiliVoice = voices.find(voice => 
      voice.lang.startsWith('sw') || 
      voice.lang.toLowerCase().includes('swahili') ||
      voice.name.toLowerCase().includes('swahili')
    );
    
    if (swahiliVoice) {
      debugLog(`Found Swahili voice: ${swahiliVoice.name} (${swahiliVoice.lang})`);
      return swahiliVoice;
    }
    
    return null;
  }

  // Load voice clips on page load
  let voiceClips = {};
  let voiceClipsLoaded = false;

  /**
   * Load recorded voice clips from server
   */
  function loadVoiceClips() {
    if (voiceClipsLoaded) return;
    
    $.ajax({
      url: '{{ route("bar.counter.get-voice-clips") }}',
      method: 'GET',
      headers: {
        'Accept': 'application/json'
      },
      success: function(response) {
        if (response.success && response.clips) {
          response.clips.forEach(clip => {
            if (!voiceClips[clip.category]) {
              voiceClips[clip.category] = {};
            }
            voiceClips[clip.category][clip.name] = clip.audio_url;
          });
          voiceClipsLoaded = true;
          debugLog('Voice clips loaded', Object.keys(voiceClips).length + ' categories');
        }
      },
      error: function() {
        debugLog('Failed to load voice clips, will use TTS only');
      }
    });
  }

  // Queue for announcements waiting for audio to be enabled
  let announcementQueue = [];

  /**
   * Play announcement using recorded audio clips + TTS for dynamic parts
   */
  function playAnnouncementWithAudio(order) {
    // Check if audio is enabled, if not, queue it
    if (!audioEnabled || !speechEnabled) {
      debugLog('Audio not enabled yet, queuing announcement for order:', order.order_number);
      announcementQueue.push(order);
      
      // Show banner if not already shown
      const banner = document.getElementById('audio-enable-banner');
      if (banner && banner.style.display === 'none') {
        banner.style.display = 'block';
        banner.innerHTML = '<div class="row align-items-center"><div class="col-md-10"><strong><i class="fa fa-volume-up"></i> New Order Waiting</strong><p class="mb-0">Click "Enable Audio" to hear the order announcement.</p></div><div class="col-md-2 text-right"><button id="enable-audio-btn" class="btn btn-primary btn-lg"><i class="fa fa-volume-up"></i> Enable Audio</button></div></div>';
      }
      
      return;
    }
    
    // Extract order number for TTS
    // Format: "ORD-01" -> "1", "ORD-02" -> "2", "ORD-10" -> "10"
    let orderNum = order.order_number;
    // Extract number after "ORD-"
    if (orderNum.includes('-')) {
      orderNum = orderNum.split('-')[1]; // Get part after "ORD-"
      // Remove leading zeros (e.g., "01" -> "1", "02" -> "2", "10" -> "10")
      orderNum = parseInt(orderNum).toString();
    } else {
      // Fallback: extract all digits
      orderNum = order.order_number.replace(/[^0-9]/g, '') || order.order_number;
      // If it's a long number, take just the last 4 digits and remove leading zeros
      if (orderNum.length > 4) {
        orderNum = parseInt(orderNum.slice(-4)).toString();
      }
    }
    const waiterName = order.waiter_name || 'Mhudumu';
    const itemsList = order.items.map(item => {
      const qty = item.quantity;
      const name = item.name;
      return `${qty} ${qty === 1 ? 'chupa' : 'chupa'} ya ${name}`;
    }).join(', ');
    const totalAmount = Math.round(order.total_amount || 0).toLocaleString('en-US');

    // Build audio sequence
    const audioSequence = [];
    
    // Try to use recorded clips, fallback to TTS
    function addAudio(text, useTTS = false) {
      if (useTTS || !voiceClips.static || !voiceClips.static[text]) {
        // Use TTS
        return { type: 'tts', text: text };
      } else {
        // Use recorded audio
        return { type: 'audio', url: voiceClips.static[text] };
      }
    }

    // Helper function to find clip by name (case-insensitive, partial match)
    function findClip(searchName) {
      if (!voiceClips.static) {
        debugLog('No voice clips loaded');
        return null;
      }
      
      const clipNames = Object.keys(voiceClips.static);
      debugLog('Available clips:', clipNames);
      debugLog('Searching for:', searchName);
      
      // Try exact match first (case-insensitive)
      const exactMatch = clipNames.find(name => 
        name.toLowerCase() === searchName.toLowerCase()
      );
      if (exactMatch) {
        debugLog(`Found exact match: ${exactMatch}`);
        return voiceClips.static[exactMatch];
      }
      
      // Try partial match (contains) - check if searchName contains clip name or vice versa
      const partialMatch = clipNames.find(name => {
        const nameLower = name.toLowerCase();
        const searchLower = searchName.toLowerCase();
        return nameLower.includes(searchLower) || searchLower.includes(nameLower);
      });
      if (partialMatch) {
        debugLog(`Found partial match: ${partialMatch}`);
        return voiceClips.static[partialMatch];
      }
      
      debugLog(`No match found for: ${searchName}`);
      return null;
    }

    // Build sequence: "Order nambari [number] kutoka meza nambari [table] kutoka kwa mhudumu [name] ameagiza [items] karibu Kili Home"
    // Try to find clips with flexible matching based on uploaded names
    const orderNambariClip = findClip('Order nambari') || findClip('Oda nambari');
    const kutokaMezaClip = findClip('Kutoka meza nambari') || findClip('kutoka meza');
    const kutokaMhudumuClip = findClip('kutoka kwa mhudumu') || findClip('Kutoka kwa') || findClip('kutoka');
    const ameagizaClip = findClip('ameagiza') || findClip('Ameagiza');
    const karibuClip = findClip('karibu Kili Home') || findClip('karibu') || findClip('asante') || findClip('Asante');

    debugLog('Clip matching results:', {
      'Order nambari': !!orderNambariClip,
      'Kutoka meza nambari': !!kutokaMezaClip,
      'kutoka kwa mhudumu': !!kutokaMhudumuClip,
      'ameagiza': !!ameagizaClip,
      'karibu Kili Home': !!karibuClip
    });

    // 1. "Order nambari"
    if (orderNambariClip) {
      audioSequence.push({ type: 'audio', url: orderNambariClip });
      debugLog('Using recorded clip: Order nambari');
    } else {
      audioSequence.push({ type: 'tts', text: 'Order nambari' });
      debugLog('Using TTS: Order nambari');
    }
    
    // 2. Order number via TTS
    audioSequence.push({ type: 'tts', text: orderNum });
    
    // 3. "Kutoka meza nambari" (if table exists)
    if (order.table_number) {
      if (kutokaMezaClip) {
        audioSequence.push({ type: 'audio', url: kutokaMezaClip });
        debugLog('Using recorded clip: Kutoka meza nambari');
      } else {
        audioSequence.push({ type: 'tts', text: 'kutoka meza nambari' });
        debugLog('Using TTS: kutoka meza nambari');
      }
      
      // Table number via TTS
      const tableNum = order.table_number.replace(/[^0-9]/g, '') || order.table_number;
      audioSequence.push({ type: 'tts', text: tableNum });
    }
    
    // 4. "kutoka kwa mhudumu"
    if (kutokaMhudumuClip) {
      audioSequence.push({ type: 'audio', url: kutokaMhudumuClip });
      debugLog('Using recorded clip: kutoka kwa mhudumu');
    } else {
      audioSequence.push({ type: 'tts', text: 'kutoka kwa mhudumu' });
      debugLog('Using TTS: kutoka kwa mhudumu');
    }
    
    // 5. Waiter name via TTS
    audioSequence.push({ type: 'tts', text: waiterName });
    
    // 6. "ameagiza"
    if (ameagizaClip) {
      audioSequence.push({ type: 'audio', url: ameagizaClip });
      debugLog('Using recorded clip: ameagiza');
    } else {
      audioSequence.push({ type: 'tts', text: 'ameagiza' });
      debugLog('Using TTS: ameagiza');
    }
    
    // 7. Items via TTS
    audioSequence.push({ type: 'tts', text: itemsList });
    
    // 8. "karibu Kili Home"
    if (karibuClip) {
      audioSequence.push({ type: 'audio', url: karibuClip });
      debugLog('Using recorded clip: karibu Kili Home');
    } else {
      audioSequence.push({ type: 'tts', text: 'karibu Kili Home' });
      debugLog('Using TTS: karibu Kili Home');
    }

    // Log the complete sequence for debugging
    debugLog('Complete audio sequence:', audioSequence.map((item, idx) => 
      `${idx + 1}. ${item.type}: ${item.text || item.url || 'N/A'}`
    ));
    debugLog(`Total items in sequence: ${audioSequence.length}`);

    // Play sequence
    playAudioSequence(audioSequence, 0);
  }

  /**
   * Play audio sequence (recorded clips + TTS)
   */
  function playAudioSequence(sequence, index) {
    if (index >= sequence.length) {
      debugLog('Audio sequence completed');
      return;
    }

    const item = sequence[index];
    
    if (item.type === 'audio') {
      // Play recorded audio
      if (!audioEnabled) {
        debugLog('Audio not enabled yet, skipping recorded clip');
        // Skip to next item
        setTimeout(() => playAudioSequence(sequence, index + 1), 100);
        return;
      }
      
      debugLog(`🎵 Attempting to play recorded audio: ${item.url}`);
      
      const audio = new Audio(item.url);
      audio.volume = 1.0;
      audio.preload = 'auto';
      
      let completed = false;
      let playbackStarted = false;
      
      const completeCallback = function() {
        if (!completed) {
          completed = true;
          debugLog(`✅ Recorded audio sequence item ${index + 1} completed`);
          // Play next in sequence immediately (no delay for seamless transition)
          playAudioSequence(sequence, index + 1);
        }
      };
      
      // Load event
      audio.onloadstart = function() {
        debugLog(`📥 Audio loading started: ${item.url}`);
      };
      
      audio.oncanplay = function() {
        debugLog(`✅ Audio can play: ${item.url}`);
      };
      
      audio.oncanplaythrough = function() {
        debugLog(`✅ Audio can play through: ${item.url}`);
        // Pre-load next item if it's audio for smoother transition
        if (index + 1 < sequence.length && sequence[index + 1].type === 'audio') {
          const nextAudio = new Audio(sequence[index + 1].url);
          nextAudio.preload = 'auto';
        }
      };
      
      // Start next item slightly before current ends (for seamless transition)
      audio.ontimeupdate = function() {
        // When audio is 90% complete, prepare next item
        if (audio.duration > 0 && audio.currentTime / audio.duration > 0.9 && !completed) {
          // Pre-start the next item preparation
          if (index + 1 < sequence.length) {
            const nextItem = sequence[index + 1];
            if (nextItem.type === 'audio') {
              // Pre-load next audio
              const nextAudio = new Audio(nextItem.url);
              nextAudio.preload = 'auto';
            } else if (nextItem.type === 'tts') {
              // Prepare TTS (voices are already loaded)
              // Nothing needed, TTS is instant
            }
          }
        }
      };
      
      audio.onplay = function() {
        playbackStarted = true;
        debugLog(`▶️ Recorded audio PLAYBACK STARTED: ${item.url}`);
      };
      
      audio.onended = function() {
        debugLog(`⏹️ Recorded audio ENDED: ${item.url}`);
        completeCallback();
      };
      
      audio.onerror = function(event) {
        errorLog('❌ Recorded audio ERROR', { 
          url: item.url, 
          error: event,
          code: audio.error ? audio.error.code : 'unknown',
          message: audio.error ? audio.error.message : 'unknown'
        });
        
        // Log specific error codes
        if (audio.error) {
          const errorMessages = {
            1: 'MEDIA_ERR_ABORTED - The user aborted the audio',
            2: 'MEDIA_ERR_NETWORK - A network error occurred',
            3: 'MEDIA_ERR_DECODE - An error occurred while decoding the audio',
            4: 'MEDIA_ERR_SRC_NOT_SUPPORTED - The audio source is not supported'
          };
          debugLog(`Error details: ${errorMessages[audio.error.code] || 'Unknown error'}`);
        }
        
        completeCallback();
      };
      
      audio.onstalled = function() {
        debugLog('⚠️ Audio playback stalled:', item.url);
      };
      
      audio.onabort = function() {
        debugLog('⚠️ Audio playback aborted:', item.url);
        completeCallback();
      };
      
      // Play with promise handling for autoplay policy
      const playPromise = audio.play();
      
      if (playPromise !== undefined) {
        playPromise.then(() => {
          playbackStarted = true;
          debugLog(`✅ Recorded audio play() promise resolved: ${item.url}`);
        }).catch((error) => {
          errorLog('❌ Failed to play recorded audio', { 
            url: item.url, 
            error: error,
            name: error.name,
            message: error.message
          });
          
          // If autoplay was blocked, try to enable and show message
          if (error.name === 'NotAllowedError' || error.name === 'NotSupportedError') {
            debugLog('⚠️ Autoplay blocked - user interaction required');
            audioEnabled = false;
            // Show banner again
            const banner = document.getElementById('audio-enable-banner');
            if (banner) {
              banner.style.display = 'block';
            }
          }
          
          // Continue anyway to not block the sequence
          completeCallback();
        });
      } else {
        // Older browser - play() doesn't return a promise
        debugLog('📞 Recorded audio play() called (no promise, older browser)');
        // Set a timeout to check if it started
        setTimeout(function() {
          if (!playbackStarted && !completed) {
            debugLog('⚠️ Audio did not start, continuing anyway');
            completeCallback();
          }
        }, 1000);
      }
      
    } else if (item.type === 'tts') {
      // Use TTS
      if (!speechEnabled) {
        debugLog('Speech not enabled yet, skipping TTS');
        // Try to enable
        enableSpeech();
        // Skip to next item
        setTimeout(() => playAudioSequence(sequence, index + 1), 100);
        return;
      }
      
      debugLog(`Playing TTS item ${index + 1}/${sequence.length}:`, item.text);
      speakSwahiliChunk(item.text, function() {
        debugLog(`TTS item ${index + 1} completed, moving to next`);
        // Play next in sequence immediately (no delay for seamless transition)
        playAudioSequence(sequence, index + 1);
      });
    } else {
      debugLog('Unknown item type, skipping:', item);
      // Skip unknown types and continue
      setTimeout(() => playAudioSequence(sequence, index + 1), 100);
    }
  }

  /**
   * Speak Swahili text using Google Translate TTS (free, no API key needed)
   * This ensures proper Swahili pronunciation even without system voices
   */
  function speakSwahili(text) {
    if (!speechEnabled) {
      debugLog('Speech not enabled yet, attempting to enable...');
      enableSpeech();
      // Try again after a short delay
      setTimeout(() => speakSwahili(text), 100);
      return;
    }

    try {
      // Google Translate TTS has a 200 character limit, so we may need to split
      // But for order announcements, they should be short enough
      if (text.length > 200) {
        debugLog('Text too long for Google TTS, splitting...');
        // Split into sentences and play sequentially
        const sentences = text.match(/[^\.!\?]+[\.!\?]+/g) || [text];
        let currentIndex = 0;
        
        function playNext() {
          if (currentIndex < sentences.length) {
            speakSwahiliChunk(sentences[currentIndex].trim(), function() {
              currentIndex++;
              if (currentIndex < sentences.length) {
                setTimeout(playNext, 500); // Small delay between chunks
              }
            });
          }
        }
        
        playNext();
        return;
      }
      
      speakSwahiliChunk(text);
      
    } catch (error) {
      errorLog('Error in speakSwahili', error);
      // Fallback to browser TTS
      speakSwahiliBrowser(text);
    }
  }

  /**
   * Speak a chunk of Swahili text using browser TTS (primary method)
   * Browser TTS is more reliable than Google TTS for dynamic content
   */
  function speakSwahiliChunk(text, onComplete) {
    if (!text || text.trim() === '') {
      debugLog('Empty text, skipping TTS');
      if (onComplete) setTimeout(onComplete, 100);
      return;
    }

    // Use browser TTS as primary method (more reliable)
    debugLog('Using browser TTS (primary method):', text);
    speakSwahiliBrowser(text, onComplete);
  }

  /**
   * Try alternative TTS method if primary fails
   */
  function tryAlternativeTTS(text, onComplete) {
    // Try using a proxy or alternative endpoint
    // For now, fallback to browser TTS
    debugLog('Using browser TTS as fallback for:', text);
    speakSwahiliBrowser(text, onComplete);
  }

  /**
   * Fallback: Use browser TTS (if Google TTS fails)
   */
  function speakSwahiliBrowser(text, onComplete) {
    if (!text || text.trim() === '') {
      debugLog('Empty text, skipping browser TTS');
      if (onComplete) setTimeout(onComplete, 100);
      return;
    }

    if (!speechSynthesis) {
      errorLog('Cannot speak: Speech synthesis not available');
      if (onComplete) setTimeout(onComplete, 100);
      return;
    }

    try {
      // Wait for any ongoing speech to finish (don't cancel, let it finish naturally)
      // But if we're queuing multiple items, we need to wait
      if (speechSynthesis.speaking) {
        debugLog('Speech already in progress, waiting...');
        // Wait a bit and retry
        setTimeout(() => speakSwahiliBrowser(text, onComplete), 100);
        return;
      }

      // Wait for voices to load if needed
      let voices = speechSynthesis.getVoices();
      if (voices.length === 0) {
        debugLog('Voices not loaded yet, waiting...');
        speechSynthesis.onvoiceschanged = function() {
          speechSynthesis.onvoiceschanged = null; // Remove listener
          speakSwahiliBrowser(text, onComplete); // Retry
        };
        return;
      }

      const utterance = new SpeechSynthesisUtterance(text);
      
      // Set Swahili language
      utterance.lang = 'sw-TZ'; // Tanzania Swahili
      
      // Set voice properties for clear speech
      utterance.rate = 0.9; // Slightly faster for numbers
      utterance.pitch = 1.0;
      utterance.volume = 1.0;

      // Try to find Swahili voice
      const swahiliVoice = getSwahiliVoice();
      
      if (swahiliVoice) {
        utterance.voice = swahiliVoice;
        utterance.lang = swahiliVoice.lang;
        debugLog(`Using Swahili voice: ${swahiliVoice.name} (${swahiliVoice.lang})`);
      } else {
        debugLog('Swahili voice not found, using default voice with sw-TZ language');
        // Try to use a female voice if available (often better for announcements)
        const femaleVoice = voices.find(v => v.name.toLowerCase().includes('female') || v.name.toLowerCase().includes('zira'));
        if (femaleVoice) {
          utterance.voice = femaleVoice;
          debugLog(`Using alternative voice: ${femaleVoice.name}`);
        }
      }

      let completed = false;
      let started = false;
      const completeCallback = function() {
        if (!completed) {
          completed = true;
          debugLog('Browser TTS completed:', text);
          if (onComplete) {
            // Call immediately for seamless transition
            onComplete();
          }
        }
      };

      utterance.onstart = function() {
        started = true;
        debugLog('✅ Browser TTS STARTED speaking:', text);
      };
      
      utterance.onend = function() {
        debugLog('✅ Browser TTS ENDED:', text);
        completeCallback();
      };
      
      utterance.onerror = function(event) {
        errorLog('❌ Browser TTS error', { text: text, error: event, type: event.type });
        if (!started) {
          debugLog('TTS never started, calling complete anyway');
        }
        completeCallback(); // Still call complete to continue sequence
      };

      debugLog('🎤 Queuing browser TTS:', text);
      speechSynthesis.speak(utterance);
      
      // Safety timeout - if TTS doesn't start within 2 seconds, continue anyway
      setTimeout(function() {
        if (!started && !completed) {
          debugLog('⚠️ TTS timeout - continuing anyway');
          completeCallback();
        }
      }, 2000);
      
    } catch (error) {
      errorLog('Error in speakSwahiliBrowser', { text: text, error: error });
      if (onComplete) setTimeout(onComplete, 100);
    }
  }

  /**
   * Format Swahili message for order announcement
   */
  function formatSwahiliMessage(order) {
    // Extract order number for TTS
    // Format: "ORD-01" -> "1", "ORD-02" -> "2", "ORD-10" -> "10"
    let orderNum = order.order_number;
    // Extract number after "ORD-"
    if (orderNum.includes('-')) {
      orderNum = orderNum.split('-')[1]; // Get part after "ORD-"
      // Remove leading zeros (e.g., "01" -> "1", "02" -> "2", "10" -> "10")
      orderNum = parseInt(orderNum).toString();
    } else {
      // Fallback: extract all digits
      orderNum = order.order_number.replace(/[^0-9]/g, '') || order.order_number;
      // If it's a long number, take just the last 4 digits and remove leading zeros
      if (orderNum.length > 4) {
        orderNum = parseInt(orderNum.slice(-4)).toString();
      }
    }
    
    // Get waiter name
    const waiterName = order.waiter_name || 'Mhudumu';
    
    // Format items list
    const itemsList = order.items.map(item => {
      const qty = item.quantity;
      const name = item.name;
      return `${qty} ${qty === 1 ? 'chupa' : 'chupa'} ya ${name}`;
    }).join(', ');

    // Format total amount in Tanzanian Shillings
    const totalAmount = order.total_amount || 0;
    const formattedAmount = Math.round(totalAmount).toLocaleString('en-US');

    // Build message: "Oda nambari ... kutoka kwa mhudumu...ameagiza .... yenye thamani ya shilingi ... Asante"
    const message = `Oda nambari ${orderNum} kutoka kwa mhudumu ${waiterName} ameagiza ${itemsList} yenye thamani ya shilingi ${formattedAmount}. Asante.`;
    
    return message;
  }

  /**
   * Check for new orders
   */
  function checkForNewOrders() {
    if (!isPolling) {
      debugLog('Polling paused');
      return;
    }

    pollCount++;
    debugLog(`Polling for new orders (Poll #${pollCount}, Last ID: ${lastOrderId})`);
    showDebugMessage(`Polling... (Last ID: ${lastOrderId})`, 'info');

    // Get CSRF token from meta tag or fallback
    const csrfToken = $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}';
    
    if (!csrfToken) {
      errorLog('CSRF token not found!');
      showDebugMessage('CSRF token missing!', 'error');
      return;
    }

    $.ajax({
      url: '{{ route("bar.counter.latest-orders") }}',
      method: 'GET',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      data: {
        last_order_id: lastOrderId
      },
      timeout: 10000, // 10 second timeout
      success: function(response) {
        errorCount = 0; // Reset error count on success
        
        if (!response) {
          errorLog('Empty response from server');
          showDebugMessage('Empty response', 'error');
          return;
        }
        
        debugLog('API response received', response);
        
        if (response.success) {
          if (response.new_orders && response.new_orders.length > 0) {
            debugLog(`Found ${response.new_orders.length} new order(s)`);
            showDebugMessage(`Found ${response.new_orders.length} new order(s)!`, 'success');
            
            // Process new orders
            response.new_orders.forEach(function(order) {
              // Skip if already announced
              if (announcedOrders.has(order.id)) {
                debugLog(`Skipping order ${order.id} (already announced)`);
                return;
              }

              // Mark as announced
              announcedOrders.add(order.id);

            // Format and speak the order
            const message = formatSwahiliMessage(order);
            debugLog('Announcing order', { id: order.id, message: message });
            showDebugMessage(`New Order: ${order.order_number}`, 'success');
            
            // Try to use recorded audio clips first, fallback to full TTS
            if (voiceClipsLoaded && Object.keys(voiceClips).length > 0) {
              debugLog('Using recorded audio clips + TTS');
              playAnnouncementWithAudio(order);
            } else {
              debugLog('Using full TTS (no recorded clips)');
              speakSwahili(message);
            }

              // Show visual notification
              showOrderNotification(order);

              // Update last order ID
              if (order.id > lastOrderId) {
                lastOrderId = order.id;
                debugLog(`Updated lastOrderId to ${lastOrderId}`);
              }
            });

            // Refresh pending count
            updatePendingCount();
          } else {
            debugLog('No new orders');
          }

          // Update last order ID from server
          if (response.latest_order_id && response.latest_order_id > lastOrderId) {
            lastOrderId = response.latest_order_id;
            debugLog(`Updated lastOrderId from server: ${lastOrderId}`);
          }
        } else {
          errorLog('API returned success: false', response);
          showDebugMessage('API error: ' + (response.error || 'Unknown error'), 'error');
        }
      },
      error: function(xhr, status, error) {
        errorLog('AJAX error', { status: status, error: error, xhr: xhr });
        
        let errorMsg = 'Error checking for orders';
        if (xhr.status === 403) {
          errorMsg = 'Permission denied (403)';
        } else if (xhr.status === 404) {
          errorMsg = 'API endpoint not found (404)';
        } else if (xhr.status === 500) {
          errorMsg = 'Server error (500)';
        } else if (status === 'timeout') {
          errorMsg = 'Request timeout';
        } else if (xhr.responseJSON && xhr.responseJSON.error) {
          errorMsg = xhr.responseJSON.error;
        }
        
        showDebugMessage(errorMsg, 'error');
        
        // If too many errors, pause polling
        if (errorCount > 10) {
          isPolling = false;
          showDebugMessage('Too many errors. Polling paused.', 'error');
          alert('Order polling stopped due to errors. Please refresh the page.');
        }
      }
    });
  }

  /**
   * Show visual notification for new order
   */
  function showOrderNotification(order) {
    // Create notification element
    const notification = $(`
      <div class="alert alert-success alert-dismissible fade show position-fixed" 
           style="top: 20px; right: 20px; z-index: 9999; min-width: 300px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
        <h5><i class="fa fa-bell"></i> Oda Mpya!</h5>
        <p class="mb-1"><strong>Oda #:</strong> ${order.order_number}</p>
        <p class="mb-1"><strong>Mhudumu:</strong> ${order.waiter_name}</p>
        <p class="mb-0"><strong>Bidhaa:</strong> ${order.items.map(i => `${i.quantity}x ${i.name}`).join(', ')}</p>
        <button type="button" class="close" data-dismiss="alert">
          <span>&times;</span>
        </button>
      </div>
    `);

    // Add to page
    $('body').append(notification);

    // Auto-remove after 10 seconds
    setTimeout(function() {
      notification.fadeOut(function() {
        $(this).remove();
      });
    }, 10000);

    // Highlight the new order in the table if visible
    const orderRow = $(`tr[data-order-id="${order.id}"]`);
    if (orderRow.length) {
      orderRow.addClass('table-success');
      orderRow.css('animation', 'pulse 2s');
      setTimeout(function() {
        orderRow.removeClass('table-success');
        orderRow.css('animation', '');
      }, 5000);
    }
  }

  /**
   * Update pending orders count
   */
  function updatePendingCount() {
    $.ajax({
      url: '{{ route("bar.counter.orders-by-status") }}',
      method: 'GET',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json'
      },
      data: {
        status: 'pending'
      },
      success: function(response) {
        if (response.success) {
          const pendingCount = response.orders.filter(o => o.status === 'pending').length;
          $('.widget-small.primary .info p b').text(pendingCount);
        }
      }
    });
  }

  // Initialize: Load voices when available
  if (speechSynthesis) {
    // Some browsers load voices asynchronously
    speechSynthesis.onvoiceschanged = function() {
      const voices = speechSynthesis.getVoices();
      debugLog(`Voices loaded: ${voices.length}`);
      
      // Log all available voices for debugging
      if (DEBUG_MODE) {
        debugLog('Available voices:', voices.map(v => `${v.name} (${v.lang})`).join(', '));
      }
      
      // Check for Swahili voice
      const swahiliVoice = getSwahiliVoice();
      
      if (swahiliVoice) {
        debugLog(`Swahili voice found: ${swahiliVoice.name} (${swahiliVoice.lang})`);
      } else {
        debugLog('Swahili voice not found');
        debugLog('Note: Browser will use default voice but will try to pronounce Swahili text');
        debugLog('To install Swahili voice:');
        debugLog('  Windows: Settings > Time & Language > Speech > Add voice');
        debugLog('  Mac: System Preferences > Accessibility > Spoken Content > System Voice');
      }
    };
    
    // Get voices immediately if already loaded
    const initialVoices = speechSynthesis.getVoices();
    if (initialVoices.length > 0) {
      debugLog(`Voices available immediately: ${initialVoices.length}`);
      const swahiliVoice = getSwahiliVoice();
      if (swahiliVoice) {
        debugLog(`Swahili voice available: ${swahiliVoice.name}`);
      }
    }
  }

  // Wait for jQuery and DOM to be ready
  $(document).ready(function() {
    debugLog('Document ready, initializing polling...');
    showDebugMessage('System initialized', 'success');
    
    // Load voice clips
    loadVoiceClips();
    
    // Start polling for new orders every 3 seconds
    pollInterval = setInterval(checkForNewOrders, 3000);
    debugLog('Polling interval started (3 seconds)');
    
    // Check immediately on page load (after 1 second delay)
    setTimeout(function() {
      debugLog('Initial poll starting...');
      checkForNewOrders();
    }, 1000);
  });

  // Pause polling when page is hidden (browser tab)
  document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
      isPolling = false;
      if (speechSynthesis) {
        speechSynthesis.cancel(); // Stop any ongoing speech
      }
      debugLog('Page hidden, polling paused');
      showDebugMessage('Page hidden - polling paused', 'warning');
    } else {
      isPolling = true;
      debugLog('Page visible, resuming polling');
      showDebugMessage('Page visible - polling resumed', 'success');
      checkForNewOrders(); // Check immediately when page becomes visible
    }
  });
  
  // Manual test function (for debugging)
  window.testOrderAnnouncement = function() {
    debugLog('Manual test triggered');
    const testOrder = {
      id: 999,
      order_number: 'TEST001',
      waiter_name: 'Test Waiter',
      items: [
        { name: 'Coca Cola', quantity: 2 },
        { name: 'Fanta', quantity: 1 }
      ]
    };
    const message = formatSwahiliMessage(testOrder);
    speakSwahili(message);
    showOrderNotification(testOrder);
  };
  
  // Expose debug info
  window.getOrderPollingStatus = function() {
    return {
      isPolling: isPolling,
      lastOrderId: lastOrderId,
      announcedOrders: Array.from(announcedOrders),
      pollCount: pollCount,
      errorCount: errorCount,
      speechEnabled: speechEnabled,
      speechAvailable: !!speechSynthesis
    };
  };

  // Add CSS for pulse animation
  if (!$('#order-notification-styles').length) {
    $('head').append(`
      <style id="order-notification-styles">
        @keyframes pulse {
          0% { background-color: #d4edda; }
          50% { background-color: #c3e6cb; }
          100% { background-color: #d4edda; }
        }
        .table-success {
          background-color: #d4edda !important;
        }
      </style>
    `);
  }
  // Filter orders by status
  $('.filter-btn').on('click', function() {
    const status = $(this).data('status');
    $('.filter-btn').removeClass('btn-primary').addClass('btn-outline-primary');
    $(this).removeClass('btn-outline-primary').addClass('btn-primary');
    
    if (status === 'all') {
      $('#orders-table tbody tr').show();
    } else {
      $('#orders-table tbody tr').hide();
      $('#orders-table tbody tr[data-status="' + status + '"]').show();
    }
  });

  // Update order status
  $(document).on('click', '.update-status-btn', function() {
    const orderId = $(this).data('order-id');
    const status = $(this).data('status');
    
    Swal.fire({
      title: 'Update Order Status?',
      text: 'Change status to ' + status + '?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, Update',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: '/bar/counter/orders/' + orderId + '/update-status',
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
          },
          data: {
            status: status
          },
          success: function(response) {
            if (response.success) {
              Swal.fire({
                icon: 'success',
                title: 'Updated!',
                text: 'Order status updated successfully'
              }).then(() => {
                location.reload();
              });
            }
          },
          error: function(xhr) {
            const error = xhr.responseJSON?.error || 'Failed to update order status';
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: error
            });
          }
        });
      }
    });
  });

  // View order details
  $(document).on('click', '.view-order-btn', function() {
    const orderId = $(this).data('order-id');
    
    // Show loading state
    $('#order-details-content').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p>Loading order details...</p></div>');
    $('#order-details-modal').modal('show');
    
    // Fetch full order details from API
    $.ajax({
      url: '/bar/orders/' + orderId + '/details',
      method: 'GET',
      success: function(response) {
        if (response.order) {
          const order = response.order;
          let content = '<div class="row">';
          
          // Order Information
          content += '<div class="col-md-6">';
          content += '<h6>Order Information</h6>';
          content += '<p><strong>Order #:</strong> ' + order.order_number + '</p>';
          if (order.table) {
            content += '<p><strong>Table:</strong> ' + order.table.table_name + '</p>';
          }
          if (order.customer_name) {
            content += '<p><strong>Customer:</strong> ' + order.customer_name + '</p>';
          }
          if (order.customer_phone) {
            content += '<p><strong>Phone:</strong> ' + order.customer_phone + '</p>';
          }
          content += '<p><strong>Date:</strong> ' + order.created_at + '</p>';
          content += '</div>';
          
          // Status & Payment
          content += '<div class="col-md-6">';
          content += '<h6>Status & Payment</h6>';
          
          // Status
          let statusBadge = '';
          if (order.status === 'pending') {
            statusBadge = '<span class="badge badge-warning">Pending</span>';
          } else if (order.status === 'served') {
            statusBadge = '<span class="badge badge-success">Served</span>';
          } else if (order.status === 'cancelled') {
            statusBadge = '<span class="badge badge-danger">Cancelled</span>';
          } else {
            statusBadge = '<span class="badge badge-secondary">' + order.status + '</span>';
          }
          content += '<p><strong>Status:</strong> ' + statusBadge + '</p>';
          
          // Payment Status
          let paymentStatusBadge = '';
          if (order.payment_status === 'paid') {
            paymentStatusBadge = '<span class="badge badge-success">Paid</span>';
          } else if (order.payment_status === 'partial') {
            paymentStatusBadge = '<span class="badge badge-warning">Partial</span>';
          } else {
            paymentStatusBadge = '<span class="badge badge-danger">Pending</span>';
          }
          content += '<p><strong>Payment Status:</strong> ' + paymentStatusBadge + '</p>';
          
          // Payment Method & Details
          if (order.payment_status === 'paid' || order.payment_status === 'partial') {
            if (order.payment_method === 'mobile_money') {
              const providerName = order.mobile_money_number || 'MOBILE MONEY';
              let displayProvider = providerName.toUpperCase();
              // Handle special cases
              if (providerName.toLowerCase().includes('mixx')) {
                displayProvider = 'MIXX BY YAS';
              } else if (providerName.toLowerCase().includes('halopesa')) {
                displayProvider = 'HALOPESA';
              } else if (providerName.toLowerCase().includes('tigo')) {
                displayProvider = 'TIGO PESA';
              } else if (providerName.toLowerCase().includes('airtel')) {
                displayProvider = 'AIRTEL MONEY';
              }
              
              content += '<p><strong>Payment Method:</strong> <span class="badge badge-success">' + displayProvider + '</span></p>';
              if (order.transaction_reference) {
                content += '<p><strong>Transaction Ref:</strong> <code>' + order.transaction_reference + '</code></p>';
              }
            } else if (order.payment_method === 'cash') {
              content += '<p><strong>Payment Method:</strong> <span class="badge badge-warning">CASH</span></p>';
            } else if (order.payment_method) {
              content += '<p><strong>Payment Method:</strong> <span class="badge badge-info">' + order.payment_method.replace('_', ' ').toUpperCase() + '</span></p>';
            }
            
            if (order.paid_by_waiter) {
              content += '<p><strong>Paid by:</strong> ' + order.paid_by_waiter + '</p>';
            }
          }
          
          content += '<p><strong>Total:</strong> <strong class="text-primary">TSh ' + parseFloat(order.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong></p>';
          if (order.paid_amount > 0) {
            content += '<p><strong>Paid:</strong> <strong class="text-success">TSh ' + parseFloat(order.paid_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong></p>';
          }
          if (order.remaining_amount > 0) {
            content += '<p><strong>Remaining:</strong> <strong class="text-danger">TSh ' + parseFloat(order.remaining_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong></p>';
          }
          content += '</div>';
          content += '</div>';
          
          // Order Items
          content += '<hr><h6>Order Items</h6>';
          if (order.items && order.items.length > 0) {
            content += '<ul class="list-unstyled">';
            order.items.forEach(function(item) {
              content += '<li class="mb-2">';
              content += '<strong>' + item.quantity + 'x</strong> ' + item.product_name;
              if (item.variant) {
                content += ' <small class="text-muted">(' + item.variant + ')</small>';
              }
              content += ' - <strong>TSh ' + parseFloat(item.total_price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong>';
              content += '</li>';
            });
            content += '</ul>';
          } else {
            content += '<p class="text-muted">No items found</p>';
          }
          
          $('#order-details-content').html(content);
        } else {
          $('#order-details-content').html('<div class="alert alert-danger">Failed to load order details.</div>');
        }
      },
      error: function(xhr) {
        const errorMsg = xhr.responseJSON?.error || 'Failed to load order details';
        $('#order-details-content').html('<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> ' + errorMsg + '</div>');
      }
    });
  });

  // Payment marking is now done in reconciliation after waiter submits daily collection
</script>
@endpush

