@extends('layouts.dashboard')

@section('title', 'Record Voice Announcements')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-microphone"></i> Record Voice Announcements</h1>
    <p>Record audio clips for order announcements</p>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="tile-title-w-btn">
        <h3 class="title">Voice Recording Studio</h3>
        <p class="text-muted">Record audio clips that will be used to announce orders</p>
      </div>
      <div class="tile-body">
        
        <!-- Recording Section -->
        <div class="mb-4">
          <h4>Add Audio Clip</h4>
          <p class="text-muted">You can either record directly or upload a pre-recorded audio file</p>
          
          <!-- Tabs for Record vs Upload -->
          <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item">
              <a class="nav-link active" id="record-tab" data-toggle="tab" href="#record-panel" role="tab">
                <i class="fa fa-microphone"></i> Record
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" id="upload-tab" data-toggle="tab" href="#upload-panel" role="tab">
                <i class="fa fa-upload"></i> Upload File
              </a>
            </li>
          </ul>

          <div class="tab-content">
            <!-- Record Tab -->
            <div class="tab-pane fade show active" id="record-panel" role="tabpanel">
              <div class="form-group">
                <label>Clip Name (for identification)</label>
                <input type="text" class="form-control" id="clip-name" placeholder="e.g., 'Oda nambari', 'kutoka kwa mhudumu', etc.">
                <small class="form-text text-muted">Use this to identify what this audio clip says</small>
              </div>
              
              <div class="form-group">
                <label>Category</label>
                <select class="form-control" id="clip-category">
                  <option value="static">Static Text (Oda nambari, kutoka kwa, etc.)</option>
                  <option value="number">Number (0-9, for order numbers and amounts)</option>
                  <option value="waiter">Waiter Name (if you want to pre-record common names)</option>
                  <option value="product">Product Name (if you want to pre-record common products)</option>
                </select>
              </div>

              <div class="form-group">
                <button id="start-recording" class="btn btn-danger">
                  <i class="fa fa-microphone"></i> Start Recording
                </button>
                <button id="stop-recording" class="btn btn-secondary" disabled>
                  <i class="fa fa-stop"></i> Stop Recording
                </button>
                <button id="play-recording" class="btn btn-info" disabled>
                  <i class="fa fa-play"></i> Play Recording
                </button>
                <button id="save-recording" class="btn btn-success" disabled>
                  <i class="fa fa-save"></i> <span id="save-recording-text">Save Recording</span>
                </button>
                <button id="cancel-update" class="btn btn-warning" style="display:none;" onclick="cancelUpdate()">
                  <i class="fa fa-times"></i> Cancel Update
                </button>
              </div>

              <div id="recording-status" class="alert" style="display:none;"></div>
              <div id="recording-timer" class="text-muted" style="display:none;"></div>
            </div>

            <!-- Upload Tab -->
            <div class="tab-pane fade" id="upload-panel" role="tabpanel">
              <div class="form-group">
                <label>Clip Name (for identification)</label>
                <input type="text" class="form-control" id="upload-clip-name" placeholder="e.g., 'Oda nambari', 'kutoka kwa mhudumu', etc.">
                <small class="form-text text-muted">Use this to identify what this audio clip says</small>
              </div>
              
              <div class="form-group">
                <label>Category</label>
                <select class="form-control" id="upload-clip-category">
                  <option value="static">Static Text (Oda nambari, kutoka kwa, etc.)</option>
                  <option value="number">Number (0-9, for order numbers and amounts)</option>
                  <option value="waiter">Waiter Name (if you want to pre-record common names)</option>
                  <option value="product">Product Name (if you want to pre-record common products)</option>
                </select>
              </div>

              <div class="form-group">
                <label>Audio File</label>
                <input type="file" class="form-control-file" id="audio-file-input" accept="audio/*">
                <small class="form-text text-muted">
                  Supported formats: MP3, WAV, OGG, WebM, M4A<br>
                  <strong>Tip:</strong> Record on your phone, then transfer the file to your computer
                </small>
              </div>

              <div class="form-group">
                <button id="play-uploaded" class="btn btn-info" disabled>
                  <i class="fa fa-play"></i> Play Uploaded File
                </button>
                <button id="save-uploaded" class="btn btn-success" disabled>
                  <i class="fa fa-save"></i> <span id="save-uploaded-text">Save Uploaded File</span>
                </button>
                <button id="cancel-update-upload" class="btn btn-warning" style="display:none;" onclick="cancelUpdate()">
                  <i class="fa fa-times"></i> Cancel Update
                </button>
              </div>

              <div id="upload-status" class="alert" style="display:none;"></div>
              <audio id="uploaded-audio-preview" controls style="display:none; width: 100%; margin-top: 10px;"></audio>
            </div>
          </div>
        </div>

        <!-- Recorded Clips List -->
        <div class="mt-4">
          <h4>Recorded Audio Clips</h4>
          <div id="clips-list" class="list-group">
            <!-- Clips will be loaded here -->
          </div>
        </div>

        <!-- Test Section -->
        <div class="mt-4">
          <h4>Test Announcement</h4>
          <div class="form-group">
            <label>Order Number</label>
            <input type="text" class="form-control" id="test-order-number" value="2025120017">
          </div>
          <div class="form-group">
            <label>Waiter Name</label>
            <input type="text" class="form-control" id="test-waiter-name" value="NANCY">
          </div>
          <div class="form-group">
            <label>Items</label>
            <input type="text" class="form-control" id="test-items" value="2 chupa ya COCA COLA, 2 chupa ya PEPSI">
          </div>
          <div class="form-group">
            <label>Total Amount</label>
            <input type="text" class="form-control" id="test-amount" value="2400">
          </div>
          <button id="test-announcement" class="btn btn-primary">
            <i class="fa fa-volume-up"></i> Test Announcement
          </button>
        </div>

      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  let mediaRecorder;
  let audioChunks = [];
  let recordedAudio = null;
  let recordingTimer = null;
  let recordingStartTime = null;

  // Debug logging helper
  function debugLog(message, data) {
    console.log('[Voice Recorder]', message, data || '');
  }

  const startBtn = document.getElementById('start-recording');
  const stopBtn = document.getElementById('stop-recording');
  const playBtn = document.getElementById('play-recording');
  const saveBtn = document.getElementById('save-recording');
  const statusDiv = document.getElementById('recording-status');
  const timerDiv = document.getElementById('recording-timer');

  // Check for MediaRecorder and getUserMedia support
  function checkMediaSupport() {
    // Check for MediaRecorder
    if (!window.MediaRecorder) {
      statusDiv.className = 'alert alert-warning';
      statusDiv.innerHTML = '⚠️ MediaRecorder not supported in this browser. Please use Chrome, Firefox, or Edge.';
      statusDiv.style.display = 'block';
      startBtn.disabled = true;
      return false;
    }

    // Check for getUserMedia (with fallbacks)
    const getUserMedia = navigator.mediaDevices?.getUserMedia ||
                        navigator.getUserMedia ||
                        navigator.webkitGetUserMedia ||
                        navigator.mozGetUserMedia ||
                        navigator.msGetUserMedia;

    if (!getUserMedia) {
      statusDiv.className = 'alert alert-warning';
      statusDiv.innerHTML = '⚠️ Microphone access not available. <br><strong>Solution:</strong> Use HTTPS or localhost. <br>Try: <code>https://192.168.100.101:8000</code> or <code>http://localhost:8000</code>';
      statusDiv.style.display = 'block';
      startBtn.disabled = true;
      return false;
    }

    return getUserMedia;
  }

  // Start recording
  startBtn.addEventListener('click', async () => {
    const getUserMedia = checkMediaSupport();
    if (!getUserMedia) {
      return;
    }

    try {
      // Use the appropriate getUserMedia method
      let stream;
      if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        // Modern API (requires HTTPS or localhost)
        stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        debugLog('Got stream via mediaDevices.getUserMedia', { 
          active: stream.active, 
          tracks: stream.getAudioTracks().length 
        });
      } else {
        // Fallback for older browsers or non-HTTPS
        stream = await new Promise((resolve, reject) => {
          const legacyGetUserMedia = navigator.getUserMedia ||
                                    navigator.webkitGetUserMedia ||
                                    navigator.mozGetUserMedia ||
                                    navigator.msGetUserMedia;
          
          if (!legacyGetUserMedia) {
            reject(new Error('getUserMedia not available'));
            return;
          }
          
          legacyGetUserMedia.call(navigator, { audio: true }, resolve, reject);
        });
        debugLog('Got stream via legacy getUserMedia', { 
          active: stream.active, 
          tracks: stream.getAudioTracks().length 
        });
      }

      // Validate stream
      if (!stream || !stream.active) {
        throw new Error('Invalid audio stream received');
      }

      const audioTracks = stream.getAudioTracks();
      if (audioTracks.length === 0) {
        stream.getTracks().forEach(track => track.stop());
        throw new Error('No audio tracks found in stream');
      }

      debugLog('Stream validated', {
        active: stream.active,
        audioTracks: audioTracks.length,
        trackLabel: audioTracks[0]?.label || 'Unknown'
      });

      // Check for MediaRecorder support and find supported MIME type
      let mimeType = '';
      const supportedTypes = [
        'audio/webm;codecs=opus',
        'audio/webm',
        'audio/mp4',
        'audio/ogg;codecs=opus',
        'audio/ogg',
        'audio/wav'
      ];

      for (const type of supportedTypes) {
        if (MediaRecorder.isTypeSupported(type)) {
          mimeType = type;
          debugLog('Using MIME type: ' + type);
          break;
        }
      }

      // If no specific type is supported, try without specifying (browser will choose)
      let recorderOptions = {};
      if (mimeType) {
        recorderOptions = { mimeType: mimeType };
      }

      try {
        mediaRecorder = new MediaRecorder(stream, recorderOptions);
      } catch (error) {
        // If MediaRecorder fails with options, try without
        console.warn('Failed to create MediaRecorder with options, trying without:', error);
        try {
          mediaRecorder = new MediaRecorder(stream);
        } catch (e) {
          throw new Error('MediaRecorder not supported: ' + e.message);
        }
      }
      audioChunks = [];

      mediaRecorder.ondataavailable = (event) => {
        if (event.data.size > 0) {
          audioChunks.push(event.data);
        }
      };

      mediaRecorder.onstop = () => {
        // Determine blob type based on what was actually recorded
        let blobType = mimeType || 'audio/webm';
        if (!blobType || blobType === '') {
          // Try to detect from first chunk or use default
          blobType = 'audio/webm';
        }
        
        // Extract base MIME type (remove codecs)
        const baseType = blobType.split(';')[0];
        
        const audioBlob = new Blob(audioChunks, { type: baseType });
        recordedAudio = URL.createObjectURL(audioBlob);
        
        // Create audio element for playback
        const audio = new Audio(recordedAudio);
        playBtn.onclick = () => audio.play();
        
        playBtn.disabled = false;
        saveBtn.disabled = false;
        statusDiv.className = 'alert alert-success';
        statusDiv.textContent = 'Recording saved! Click Play to preview or Save to store.';
        statusDiv.style.display = 'block';
        
        // Stop all tracks
        stream.getTracks().forEach(track => track.stop());
      };

      mediaRecorder.onerror = (event) => {
        console.error('MediaRecorder error:', event);
        statusDiv.className = 'alert alert-danger';
        statusDiv.textContent = 'Recording error occurred. Please try again.';
        statusDiv.style.display = 'block';
        startBtn.disabled = false;
        stopBtn.disabled = true;
      };

      // Start recording with error handling
      try {
        // Set timeslice to ensure data is available
        mediaRecorder.start(100); // Collect data every 100ms
        debugLog('MediaRecorder started', { state: mediaRecorder.state, mimeType: mimeType });
        
        startBtn.disabled = true;
        stopBtn.disabled = false;
        playBtn.disabled = true;
        saveBtn.disabled = true;
        
        statusDiv.className = 'alert alert-danger';
        statusDiv.textContent = '🔴 Recording... Click Stop when finished.';
        statusDiv.style.display = 'block';
        
        // Start timer
        recordingStartTime = Date.now();
        timerDiv.style.display = 'block';
        recordingTimer = setInterval(() => {
          const elapsed = Math.floor((Date.now() - recordingStartTime) / 1000);
          timerDiv.textContent = `Recording: ${elapsed}s`;
        }, 1000);
      } catch (error) {
        console.error('Error starting MediaRecorder:', error);
        statusDiv.className = 'alert alert-danger';
        statusDiv.textContent = 'Error starting recorder: ' + error.message + '. Please try a different browser (Chrome recommended).';
        statusDiv.style.display = 'block';
        startBtn.disabled = false;
        stopBtn.disabled = true;
        stream.getTracks().forEach(track => track.stop());
      }

    } catch (error) {
      console.error('Error accessing microphone:', error);
      debugLog('Error details:', { name: error.name, message: error.message, stack: error.stack });
      
      let errorMessage = 'Error accessing microphone. ';
      
      if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
        errorMessage += '<strong>Permission Denied</strong><br>Please allow microphone access in your browser settings and try again.<br>';
        errorMessage += '<small>Look for the microphone icon in your browser\'s address bar and click "Allow"</small>';
      } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
        errorMessage += '<strong>No Microphone Found</strong><br>Please connect a microphone and try again.';
      } else if (error.name === 'NotSupportedError' || error.name === 'ConstraintNotSatisfiedError') {
        errorMessage += '<strong>Not Supported</strong><br>Your browser or device doesn\'t support audio recording.<br>';
        errorMessage += 'Please use Chrome, Firefox, or Edge browser.';
      } else if (error.message && error.message.includes('object can not be found')) {
        errorMessage += '<strong>MediaRecorder Error</strong><br>';
        errorMessage += 'This error usually means:<br>';
        errorMessage += '1. Your browser doesn\'t support MediaRecorder<br>';
        errorMessage += '2. Try using <strong>Google Chrome</strong> (recommended)<br>';
        errorMessage += '3. Or try <strong>Microsoft Edge</strong> or <strong>Firefox</strong><br>';
        errorMessage += '<br><strong>Browser Compatibility:</strong><br>';
        errorMessage += '✅ Chrome 47+ (Recommended)<br>';
        errorMessage += '✅ Edge 79+<br>';
        errorMessage += '✅ Firefox 25+<br>';
        errorMessage += '❌ Safari (Limited support)<br>';
        errorMessage += '❌ Internet Explorer (Not supported)';
      } else if (!navigator.mediaDevices) {
        errorMessage += '<br><strong>Solution:</strong> Use HTTPS or localhost. <br>Try: <code>https://192.168.100.101:8000</code> or <code>http://localhost:8000</code>';
      } else {
        errorMessage += '<strong>Error:</strong> ' + (error.message || error.name || 'Unknown error occurred.');
        errorMessage += '<br><br><strong>Try:</strong><br>';
        errorMessage += '1. Refresh the page<br>';
        errorMessage += '2. Use Google Chrome browser<br>';
        errorMessage += '3. Check if microphone is connected and working<br>';
        errorMessage += '4. Allow microphone permissions when prompted';
      }
      
      statusDiv.className = 'alert alert-danger';
      statusDiv.innerHTML = errorMessage;
      statusDiv.style.display = 'block';
      startBtn.disabled = false;
      stopBtn.disabled = true;
    }
  });

  // Stop recording
  stopBtn.addEventListener('click', () => {
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
      mediaRecorder.stop();
      startBtn.disabled = false;
      stopBtn.disabled = true;
      
      if (recordingTimer) {
        clearInterval(recordingTimer);
        recordingTimer = null;
      }
    }
  });

  // Save recording
  saveBtn.addEventListener('click', async () => {
    const clipName = document.getElementById('clip-name').value.trim();
    const category = document.getElementById('clip-category').value;

    if (!clipName) {
      alert('Please enter a clip name');
      return;
    }

    if (!recordedAudio) {
      alert('No recording to save');
      return;
    }

    // Convert blob to base64 for storage
    const audioBlob = await fetch(recordedAudio).then(r => r.blob());
    const reader = new FileReader();
    
    reader.onloadend = function() {
      const base64Audio = reader.result;
      
      // Determine if updating or creating new
      const isUpdate = updatingClipId !== null;
      const url = isUpdate 
        ? `/bar/counter/voice-clips/${updatingClipId}`
        : '{{ route("bar.counter.save-voice-clip") }}';
      const method = isUpdate ? 'PUT' : 'POST';
      
      // Save to server
      $.ajax({
        url: url,
        method: method,
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Accept': 'application/json'
        },
        data: {
          name: clipName,
          category: category,
          audio: base64Audio
        },
        success: function(response) {
          if (response.success) {
            alert(isUpdate ? 'Audio clip updated successfully!' : 'Audio clip saved successfully!');
            loadClips();
            // Reset form
            cancelUpdate();
          }
        },
        error: function(xhr) {
          alert('Error ' + (isUpdate ? 'updating' : 'saving') + ' audio clip: ' + (xhr.responseJSON?.error || 'Unknown error'));
        }
      });
    };
    
    reader.readAsDataURL(audioBlob);
  });

  // Test announcement
  document.getElementById('test-announcement').addEventListener('click', () => {
    const orderNumber = document.getElementById('test-order-number').value;
    const waiterName = document.getElementById('test-waiter-name').value;
    const items = document.getElementById('test-items').value;
    const amount = document.getElementById('test-amount').value;
    
    // This will use the playAnnouncement function from the main system
    if (window.testAnnouncementWithAudio) {
      window.testAnnouncementWithAudio(orderNumber, waiterName, items, amount);
    } else {
      alert('Please load the main counter page first to test announcements');
    }
  });

  // Track if we're updating an existing clip
  let updatingClipId = null;

  // Load saved clips
  function loadClips() {
    $.ajax({
      url: '{{ route("bar.counter.get-voice-clips") }}',
      method: 'GET',
      headers: {
        'Accept': 'application/json'
      },
      success: function(response) {
        const clipsList = document.getElementById('clips-list');
        clipsList.innerHTML = '';
        
        if (response.clips && response.clips.length > 0) {
          response.clips.forEach(clip => {
            const clipDiv = document.createElement('div');
            clipDiv.className = 'list-group-item';
            clipDiv.innerHTML = `
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <strong>${clip.name}</strong> 
                  <span class="badge badge-secondary">${clip.category}</span>
                  <br><small class="text-muted">${clip.created_at}</small>
                </div>
                <div>
                  <audio controls src="${clip.audio_url}" style="max-width: 300px;"></audio>
                  <button class="btn btn-sm btn-warning ml-2" onclick="updateClip(${clip.id}, '${clip.name}', '${clip.category}')">
                    <i class="fa fa-edit"></i> Update
                  </button>
                  <button class="btn btn-sm btn-danger ml-2" onclick="deleteClip(${clip.id})">
                    <i class="fa fa-trash"></i> Delete
                  </button>
                </div>
              </div>
            `;
            clipsList.appendChild(clipDiv);
          });
        } else {
          clipsList.innerHTML = '<p class="text-muted">No audio clips recorded yet.</p>';
        }
      }
    });
  }

  // Update clip - populate form with existing data
  function updateClip(clipId, clipName, clipCategory) {
    updatingClipId = clipId;
    
    // Populate form fields
    document.getElementById('clip-name').value = clipName;
    document.getElementById('clip-category').value = clipCategory;
    document.getElementById('upload-clip-name').value = clipName;
    document.getElementById('upload-clip-category').value = clipCategory;
    
    // Update button text
    document.getElementById('save-recording-text').textContent = 'Update Recording';
    document.getElementById('save-uploaded-text').textContent = 'Update Uploaded File';
    
    // Show cancel buttons
    document.getElementById('cancel-update').style.display = 'inline-block';
    document.getElementById('cancel-update-upload').style.display = 'inline-block';
    
    // Show message
    const statusDiv = document.getElementById('recording-status');
    statusDiv.className = 'alert alert-info';
    statusDiv.innerHTML = `🔄 <strong>Updating:</strong> ${clipName}<br>Record a new audio or upload a new file to replace the existing one. Click "Cancel Update" to cancel.`;
    statusDiv.style.display = 'block';
    
    // Scroll to form
    document.querySelector('#record-panel').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    
    // Switch to record tab
    document.getElementById('record-tab').click();
  }

  // Cancel update
  function cancelUpdate() {
    updatingClipId = null;
    
    // Reset form fields
    document.getElementById('clip-name').value = '';
    document.getElementById('upload-clip-name').value = '';
    
    // Reset button text
    document.getElementById('save-recording-text').textContent = 'Save Recording';
    document.getElementById('save-uploaded-text').textContent = 'Save Uploaded File';
    
    // Hide cancel buttons
    document.getElementById('cancel-update').style.display = 'none';
    document.getElementById('cancel-update-upload').style.display = 'none';
    
    // Hide status message
    document.getElementById('recording-status').style.display = 'none';
    
    // Reset recording state
    recordedAudio = null;
    playBtn.disabled = true;
    saveBtn.disabled = true;
  }

  function deleteClip(clipId) {
    if (!confirm('Are you sure you want to delete this audio clip?')) return;
    
    $.ajax({
      url: `/bar/counter/voice-clips/${clipId}`,
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json'
      },
      success: function() {
        loadClips();
      }
    });
  }

  // Detect browser and show compatibility info
  function detectBrowser() {
    const userAgent = navigator.userAgent;
    let browser = 'Unknown';
    let version = '';
    
    if (userAgent.indexOf('Chrome') > -1 && userAgent.indexOf('Edg') === -1) {
      browser = 'Chrome';
      const match = userAgent.match(/Chrome\/(\d+)/);
      version = match ? match[1] : '';
    } else if (userAgent.indexOf('Firefox') > -1) {
      browser = 'Firefox';
      const match = userAgent.match(/Firefox\/(\d+)/);
      version = match ? match[1] : '';
    } else if (userAgent.indexOf('Edg') > -1) {
      browser = 'Edge';
      const match = userAgent.match(/Edg\/(\d+)/);
      version = match ? match[1] : '';
    } else if (userAgent.indexOf('Safari') > -1 && userAgent.indexOf('Chrome') === -1) {
      browser = 'Safari';
      const match = userAgent.match(/Version\/(\d+)/);
      version = match ? match[1] : '';
    }
    
    return { browser, version };
  }

  // Handle file upload
  let uploadedAudioFile = null;
  const audioFileInput = document.getElementById('audio-file-input');
  const playUploadedBtn = document.getElementById('play-uploaded');
  const saveUploadedBtn = document.getElementById('save-uploaded');
  const uploadedAudioPreview = document.getElementById('uploaded-audio-preview');
  const uploadStatusDiv = document.getElementById('upload-status');

  audioFileInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) {
      uploadedAudioFile = null;
      playUploadedBtn.disabled = true;
      saveUploadedBtn.disabled = true;
      uploadedAudioPreview.style.display = 'none';
      return;
    }

    // Validate file type
    if (!file.type.startsWith('audio/')) {
      uploadStatusDiv.className = 'alert alert-danger';
      uploadStatusDiv.textContent = 'Please select an audio file (MP3, WAV, OGG, etc.)';
      uploadStatusDiv.style.display = 'block';
      return;
    }

    uploadedAudioFile = file;
    
    // Create preview URL
    const fileURL = URL.createObjectURL(file);
    uploadedAudioPreview.src = fileURL;
    uploadedAudioPreview.style.display = 'block';
    
    playUploadedBtn.disabled = false;
    saveUploadedBtn.disabled = true; // Will enable after preview
    
    uploadStatusDiv.className = 'alert alert-success';
    uploadStatusDiv.textContent = `File loaded: ${file.name} (${(file.size / 1024).toFixed(2)} KB)`;
    uploadStatusDiv.style.display = 'block';

    // Enable save after file is loaded
    uploadedAudioPreview.addEventListener('loadeddata', function() {
      saveUploadedBtn.disabled = false;
    }, { once: true });
  });

  // Play uploaded file
  playUploadedBtn.addEventListener('click', function() {
    if (uploadedAudioPreview.src) {
      uploadedAudioPreview.play();
    }
  });

  // Save uploaded file
  saveUploadedBtn.addEventListener('click', function() {
    if (!uploadedAudioFile) {
      alert('Please select an audio file first');
      return;
    }

    const clipName = document.getElementById('upload-clip-name').value.trim();
    const category = document.getElementById('upload-clip-category').value;

    if (!clipName) {
      alert('Please enter a clip name');
      return;
    }

    // Use FormData to send file
    const formData = new FormData();
    formData.append('name', clipName);
    formData.append('category', category);
    formData.append('audio_file', uploadedAudioFile);
    
    // Determine if updating or creating new
    const isUpdate = updatingClipId !== null;
    const url = isUpdate 
      ? `/bar/counter/voice-clips/${updatingClipId}`
      : '{{ route("bar.counter.save-voice-clip") }}';
    const method = isUpdate ? 'PUT' : 'POST';
      
    // Save to server
    $.ajax({
      url: url,
      method: method,
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json'
      },
      data: formData,
      processData: false,
      contentType: false,
        success: function(response) {
          if (response.success) {
            alert(isUpdate ? 'Audio clip updated successfully!' : 'Audio clip saved successfully!');
            loadClips();
            // Reset form
            document.getElementById('audio-file-input').value = '';
            uploadedAudioFile = null;
            playUploadedBtn.disabled = true;
            saveUploadedBtn.disabled = true;
            uploadedAudioPreview.style.display = 'none';
            uploadStatusDiv.style.display = 'none';
            cancelUpdate();
          }
        },
      error: function(xhr) {
        const errorMsg = xhr.responseJSON?.error || xhr.responseJSON?.message || 'Unknown error';
        alert('Error ' + (isUpdate ? 'updating' : 'saving') + ' audio clip: ' + errorMsg);
        console.error('Upload error:', xhr);
      }
    });
  });

  // Load clips on page load and check support
  $(document).ready(function() {
    loadClips();
    checkMediaSupport();
    
    // Show browser info
    const browserInfo = detectBrowser();
    const browserDiv = document.createElement('div');
    browserDiv.className = 'alert alert-info';
    browserDiv.innerHTML = `
      <strong>Browser:</strong> ${browserInfo.browser} ${browserInfo.version || ''}
      <br><strong>MediaRecorder:</strong> ${window.MediaRecorder ? '✅ Supported' : '❌ Not Supported'}
      <br><strong>getUserMedia:</strong> ${navigator.mediaDevices?.getUserMedia ? '✅ Available' : '❌ Not Available'}
      ${!window.MediaRecorder ? '<br><br><strong>⚠️ Recommendation:</strong> Please use <strong>Google Chrome</strong> for best compatibility.' : ''}
    `;
    document.querySelector('.tile-body').insertBefore(browserDiv, document.querySelector('.tile-body').firstChild);
    
    // Show helpful message if on HTTP (not HTTPS or localhost)
    if (window.location.protocol === 'http:' && 
        !window.location.hostname.includes('localhost') && 
        !window.location.hostname.includes('127.0.0.1')) {
      const httpWarning = document.createElement('div');
      httpWarning.className = 'alert alert-warning';
      httpWarning.innerHTML = `
        <strong>⚠️ Note:</strong> Microphone access requires HTTPS or localhost. 
        <br>Current URL: <code>${window.location.href}</code>
        <br><strong>Solutions:</strong>
        <ul>
          <li>Use <code>http://localhost:8000</code> instead</li>
          <li>Or set up HTTPS for your server</li>
        </ul>
      `;
      document.querySelector('.tile-body').insertBefore(httpWarning, document.querySelector('.tile-body').firstChild);
    }
  });
</script>
@endpush

