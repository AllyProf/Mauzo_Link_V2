/**
 * JavaScript Test Script for Order Polling Simulation
 * 
 * This script simulates the real-time polling mechanism
 * that runs on the counter screen.
 * 
 * Usage: 
 * 1. Open browser console on the counter page
 * 2. Copy and paste this script
 * 3. Or include it in a test HTML page
 */

(function() {
    console.log('========================================');
    console.log('Order Polling Simulation Test');
    console.log('========================================\n');

    // Configuration
    const POLL_INTERVAL = 3000; // 3 seconds
    const API_ENDPOINT = '/bar/counter/latest-orders';
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';

    // State
    let lastOrderId = 0;
    let announcedOrders = new Set();
    let isPolling = false;
    let pollInterval = null;
    let requestCount = 0;
    let newOrderCount = 0;

    // Speech synthesis setup
    const speechSynthesis = window.speechSynthesis;
    let isSpeechSupported = !!speechSynthesis;

    console.log('Configuration:');
    console.log(`  Poll Interval: ${POLL_INTERVAL}ms`);
    console.log(`  API Endpoint: ${API_ENDPOINT}`);
    console.log(`  Speech Synthesis: ${isSpeechSupported ? 'Supported' : 'Not Supported'}`);
    console.log(`  CSRF Token: ${CSRF_TOKEN ? 'Found' : 'Missing'}\n`);

    /**
     * Format Swahili message for order announcement
     */
    function formatSwahiliMessage(order) {
        const orderNum = order.order_number.replace(/[^0-9]/g, '') || order.order_number;
        const itemsList = order.items.map(item => {
            const qty = item.quantity;
            const name = item.name;
            return `${qty} ${qty === 1 ? 'chupa' : 'chupa'} ya ${name}`;
        }).join(', ');
        return `Oda namba ${orderNum} imeombwa: ${itemsList}.`;
    }

    /**
     * Speak Swahili text
     */
    function speakSwahili(text) {
        if (!isSpeechSupported) {
            console.warn('Speech synthesis not available');
            return;
        }

        speechSynthesis.cancel();

        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'sw-TZ';
        utterance.rate = 0.9;
        utterance.pitch = 1.0;
        utterance.volume = 1.0;

        const voices = speechSynthesis.getVoices();
        const swahiliVoice = voices.find(voice => 
            voice.lang.startsWith('sw') || 
            voice.lang.includes('Swahili') ||
            voice.name.includes('Swahili')
        );
        
        if (swahiliVoice) {
            utterance.voice = swahiliVoice;
            console.log(`Using Swahili voice: ${swahiliVoice.name}`);
        }

        utterance.onstart = () => console.log('🔊 Speech started');
        utterance.onend = () => console.log('✓ Speech completed');
        utterance.onerror = (event) => console.error('Speech error:', event.error);

        speechSynthesis.speak(utterance);
    }

    /**
     * Check for new orders
     */
    async function checkForNewOrders() {
        if (!isPolling) return;

        requestCount++;
        const timestamp = new Date().toLocaleTimeString();
        console.log(`[${timestamp}] Polling for new orders (Request #${requestCount})...`);

        try {
            const response = await fetch(`${API_ENDPOINT}?last_order_id=${lastOrderId}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(CSRF_TOKEN && { 'X-CSRF-TOKEN': CSRF_TOKEN })
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            if (data.success) {
                if (data.new_orders.length > 0) {
                    console.log(`✓ Found ${data.new_orders.length} new order(s)`);
                    newOrderCount += data.new_orders.length;

                    data.new_orders.forEach(order => {
                        if (announcedOrders.has(order.id)) {
                            console.log(`  ⏭ Skipping order #${order.order_number} (already announced)`);
                            return;
                        }

                        announcedOrders.add(order.id);
                        console.log(`  📦 New Order: #${order.order_number}`);
                        console.log(`     Waiter: ${order.waiter_name}`);
                        console.log(`     Items: ${order.items.length}`);
                        console.log(`     Total: TSh ${order.total_amount.toLocaleString()}`);

                        const message = formatSwahiliMessage(order);
                        console.log(`     Message: "${message}"`);

                        // Speak the order
                        speakSwahili(message);
                    });
                } else {
                    console.log('  No new orders');
                }

                if (data.latest_order_id > lastOrderId) {
                    lastOrderId = data.latest_order_id;
                    console.log(`  Updated last order ID: ${lastOrderId}`);
                }
            } else {
                console.error('API returned error:', data.error);
            }
        } catch (error) {
            console.error('Polling error:', error.message);
        }
    }

    /**
     * Start polling
     */
    function startPolling() {
        if (isPolling) {
            console.warn('Polling is already running');
            return;
        }

        isPolling = true;
        console.log('\n🚀 Starting polling...');
        console.log(`   Interval: ${POLL_INTERVAL}ms`);
        console.log(`   Initial last order ID: ${lastOrderId}\n`);

        // Check immediately
        checkForNewOrders();

        // Then poll every interval
        pollInterval = setInterval(checkForNewOrders, POLL_INTERVAL);
    }

    /**
     * Stop polling
     */
    function stopPolling() {
        if (!isPolling) {
            console.warn('Polling is not running');
            return;
        }

        isPolling = false;
        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }

        if (speechSynthesis) {
            speechSynthesis.cancel();
        }

        console.log('\n⏹ Polling stopped');
        console.log(`   Total requests: ${requestCount}`);
        console.log(`   New orders found: ${newOrderCount}`);
    }

    /**
     * Get statistics
     */
    function getStats() {
        return {
            isPolling,
            lastOrderId,
            announcedOrders: Array.from(announcedOrders),
            requestCount,
            newOrderCount,
            speechSupported: isSpeechSupported
        };
    }

    // Expose functions to global scope for testing
    window.orderPollingTest = {
        start: startPolling,
        stop: stopPolling,
        check: checkForNewOrders,
        stats: getStats,
        setLastOrderId: (id) => { lastOrderId = id; console.log(`Last order ID set to: ${id}`); },
        clearAnnounced: () => { announcedOrders.clear(); console.log('Announced orders cleared'); },
        testSpeech: (message) => speakSwahili(message || 'Oda namba 2025120001 imeombwa: 2 chupa ya Coca Cola.')
    };

    console.log('\n✅ Test script loaded!');
    console.log('\nAvailable commands:');
    console.log('  orderPollingTest.start()      - Start polling');
    console.log('  orderPollingTest.stop()       - Stop polling');
    console.log('  orderPollingTest.check()     - Check once immediately');
    console.log('  orderPollingTest.stats()     - Get statistics');
    console.log('  orderPollingTest.setLastOrderId(id) - Set last order ID');
    console.log('  orderPollingTest.clearAnnounced()   - Clear announced orders');
    console.log('  orderPollingTest.testSpeech(message) - Test speech synthesis');
    console.log('\nTo start testing, run: orderPollingTest.start()\n');
})();







