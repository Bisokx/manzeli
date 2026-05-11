<?php
// ============================================================
// MANZELI CHATBOT — Groq API Backend
// Called via AJAX from the chatbot widget
// ============================================================
if (isset($_POST['chatbot_query'])) {
    session_start();
    require_once __DIR__ . '/db.php';
    
    header('Content-Type: application/json');
    
    $message = trim($_POST['chatbot_query']);
    $lower = mb_strtolower($message);
    
    if (empty($message)) {
        echo json_encode(['type' => 'text', 'message' => 'Please type a message.']);
        exit;
    }
    
    // Check if user is asking about specific properties — search DB first
    $dbResult = searchProperties($pdo, $lower, $message);
    if ($dbResult && !empty($dbResult['properties'])) {
        echo json_encode(['type' => 'properties', 'message' => $dbResult['message'], 'properties' => $dbResult['properties']]);
        exit;
    }
    
    // Use Groq AI for everything else
    $aiResponse = callGroqAPI($message);
    echo json_encode(['type' => 'text', 'message' => $aiResponse]);
    exit;
}

// ============================================================
// GROQ API CALL
// ============================================================
function callGroqAPI($userMessage) {
    $apiKey = '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';
    
    $systemPrompt = "You are Manzeli's AI assistant — a helpful, friendly chatbot for a Lebanon-based real estate platform called Manzeli (منزلي meaning 'My Home' in Arabic). Built by Bassim Kazouini as a Senior Project 2024-2025.

About Manzeli:
- Real estate platform for renting, buying properties, and purchasing land in Lebanon
- Three listing types: Rent (book with dates), Buy (contact seller form), Land (request info form)
- Users can browse listings, filter by type/location/price/bedrooms, save favorites, leave reviews
- Guests can book rentals (credit card or pay on arrival), send purchase inquiries
- Hosts can list properties for free with photos, amenities, and descriptions
- 15 amenities available: WiFi, Parking, Pool, A/C, Generator, Sea View, Mountain View, Balcony, Garden, Gym, Elevator, Security, Furnished, Heating, Fireplace
- Platform covers all Lebanon: Beirut, Jounieh, Batroun, Byblos, Tripoli, Sidon, Zahle, and more
- Contact: info@manzeli.com | +961 70 322 369 | West Bekaa, Sohmor, Lebanon
- Features: AI chatbot, booking calendar, review system, messaging between guest and host, Google login, favorites, admin panel

How to use Manzeli:
- Rent: Explore → Rent → pick property → choose dates → Book Now
- Buy: Explore → Buy → pick property → fill Contact Seller form
- Land: Explore → Land → pick property → fill Request Info form
- List property: Register as Host → Dashboard → Add Property
- Save property: Click heart icon on any listing
- Reviews: Log in → go to property → write review with star rating (1-5)
- Messages: Send message to host from property page, host replies from dashboard

Guidelines:
- Keep responses concise (2-4 sentences when possible)
- Be warm, professional, and helpful
- Use English primarily but understand Arabic terms
- If asked about specific prices or availability, suggest browsing the Listings page
- Don't make up property listings or prices
- For questions outside your scope, politely redirect to Manzeli topics
- You can use emojis sparingly for friendliness 🏠";

    // Build conversation with session history
    if (!isset($_SESSION['chat_history'])) {
        $_SESSION['chat_history'] = [];
    }
    
    $messages = [['role' => 'system', 'content' => $systemPrompt]];
    
    // Add last 10 messages for context
    foreach (array_slice($_SESSION['chat_history'], -10) as $msg) {
        $messages[] = $msg;
    }
    $messages[] = ['role' => 'user', 'content' => $userMessage];
    
    $data = [
        'model' => 'llama-3.3-70b-versatile',
        'messages' => $messages,
        'max_tokens' => 500,
        'temperature' => 0.7
    ];
    
    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        return "Sorry, I'm having trouble connecting right now. Please try again in a moment.";
    }
    
    $decoded = json_decode($response, true);
    
    if ($httpCode !== 200) {
        error_log('Groq API error: ' . $response);
        return "I'm temporarily unavailable. Please try again shortly.";
    }
    
    $reply = $decoded['choices'][0]['message']['content'] ?? "Sorry, I couldn't process that. Please try again.";
    
    // Save to session history
    $_SESSION['chat_history'][] = ['role' => 'user', 'content' => $userMessage];
    $_SESSION['chat_history'][] = ['role' => 'assistant', 'content' => $reply];
    if (count($_SESSION['chat_history']) > 20) {
        $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -20);
    }
    
    return $reply;
}

// ============================================================
// DATABASE SEARCH (for property queries)
// ============================================================
function searchProperties($pdo, $lower, $original) {
    $propertyTriggers = ['show me', 'find me', 'search', 'looking for', 'i want', 'i need', 'available',
        'apartment under', 'house under', 'property under', 'less than', 'below', 'under $', 'under $',
        'bedroom in', 'bedrooms in', 'rent in', 'buy in', 'land in'];
    
    $isPropertyQuery = false;
    foreach ($propertyTriggers as $trigger) {
        if (strpos($lower, $trigger) !== false) {
            $isPropertyQuery = true;
            break;
        }
    }
    
    if (!$isPropertyQuery) return null;
    
    $conditions = ["p.status = 'active'"];
    $params = [];
    
    if (strpos($lower, 'rent') !== false) {
        $conditions[] = "p.listing_type = 'rent'";
    } elseif (strpos($lower, 'buy') !== false || strpos($lower, 'sale') !== false || strpos($lower, 'purchase') !== false) {
        $conditions[] = "p.listing_type = 'buy'";
    } elseif (strpos($lower, 'land') !== false || strpos($lower, 'plot') !== false) {
        $conditions[] = "p.listing_type = 'land'";
    }
    
    if (preg_match('/(?:under|less than|below|cheaper than|max|up to)\s*\$?\s*(\d[\d,]*)/i', $original, $m)) {
        $conditions[] = "p.price <= ?";
        $params[] = (int)str_replace(',', '', $m[1]);
    }
    if (preg_match('/(?:over|above|more than|at least|min)\s*\$?\s*(\d[\d,]*)/i', $original, $m)) {
        $conditions[] = "p.price >= ?";
        $params[] = (int)str_replace(',', '', $m[1]);
    }
    
    if (preg_match('/(\d+)\s*(?:bed|bedroom|bedrooms|beds|br|room)/i', $original, $m)) {
        $conditions[] = "p.bedrooms = ?";
        $params[] = (int)$m[1];
    }
    
    $locations = ['beirut','jounieh','batroun','byblos','jbeil','tripoli','sidon','saida',
        'zahle','keserwan','metn','chouf','baabda','aley','broummana','dbayeh',
        'antelias','hamra','achrafieh','verdun','rabieh','hazmieh','tyre','nabatieh','baalbek'];
    
    foreach ($locations as $loc) {
        if (strpos($lower, $loc) !== false) {
            $conditions[] = "LOWER(p.location) LIKE ?";
            $params[] = '%' . $loc . '%';
            break;
        }
    }
    
    $where = implode(' AND ', $conditions);
    $sql = "SELECT p.id, p.title, p.price, p.price_period, p.listing_type, p.location, 
                   p.bedrooms, p.bathrooms, p.area_sqm as area,
                   (SELECT image_path FROM property_images WHERE property_id = p.id AND is_main = 1 LIMIT 1) as image
            FROM properties p WHERE {$where} ORDER BY p.created_at DESC LIMIT 5";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
    }
    
    if (empty($properties)) return null;
    
    $count = count($properties);
    return [
        'message' => "I found {$count} " . ($count === 1 ? 'property' : 'properties') . " for you:",
        'properties' => $properties
    ];
}
?>
<!-- ============================================================
     MANZELI CHATBOT WIDGET — Groq AI Powered
     ============================================================ -->
<style>
.chatbot-window{position:fixed;bottom:95px;right:24px;width:380px;height:520px;background:#fff;border-radius:16px;box-shadow:0 10px 40px rgba(0,0,0,.15);z-index:1000;display:flex;flex-direction:column;overflow:hidden;opacity:0;visibility:hidden;transform:translateY(20px) scale(.95);transition:opacity .3s,transform .3s,visibility .3s}
.chatbot-window.open{opacity:1;visibility:visible;transform:translateY(0) scale(1)}
.cb-header{background:linear-gradient(135deg,var(--primary,#0ABAB5),var(--primary-dark,#089E9A));color:#fff;padding:14px 18px;display:flex;align-items:center;gap:10px;flex-shrink:0}
.cb-header-icon{width:38px;height:38px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px}
.cb-header-info h4{margin:0;font-size:15px;font-weight:600}
.cb-header-info span{font-size:11px;opacity:.85}
.cb-close{margin-left:auto;background:0 0;border:none;color:#fff;cursor:pointer;font-size:22px;padding:4px 8px;border-radius:50%;transition:background .2s;line-height:1}
.cb-close:hover{background:rgba(255,255,255,.2)}
.cb-messages{flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:10px;background:#f5f5f5}
.cb-messages::-webkit-scrollbar{width:4px}
.cb-messages::-webkit-scrollbar-thumb{background:#ccc;border-radius:10px}
.cb-msg{max-width:85%;padding:10px 14px;border-radius:16px;font-size:13.5px;line-height:1.5;word-wrap:break-word;animation:cbA .3s ease}
@keyframes cbA{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
.cb-msg.bot{background:#fff;color:#333;align-self:flex-start;border-bottom-left-radius:4px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.cb-msg.user{background:linear-gradient(135deg,var(--primary,#0ABAB5),var(--primary-dark,#089E9A));color:#fff;align-self:flex-end;border-bottom-right-radius:4px}
.cb-prop{background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);margin:4px 0;animation:cbA .3s ease;max-width:90%;align-self:flex-start}
.cb-prop img{width:100%;height:120px;object-fit:cover}
.cb-prop-body{padding:10px 12px}
.cb-prop-title{font-size:13px;font-weight:600;color:#333;margin:0 0 4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.cb-prop-info{font-size:11.5px;color:#777;margin:0 0 6px}
.cb-prop-price{font-size:14px;font-weight:700;color:var(--primary,#0ABAB5)}
.cb-prop-price span{font-size:11px;font-weight:400;color:#999}
.cb-prop-link{display:inline-block;margin-top:6px;font-size:12px;color:var(--primary,#0ABAB5);text-decoration:none;font-weight:600}
.cb-prop-link:hover{text-decoration:underline}
.cb-typing{display:none;align-self:flex-start;padding:10px 18px;background:#fff;border-radius:16px;border-bottom-left-radius:4px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.cb-typing.show{display:flex;gap:4px;align-items:center}
.cb-typing span{width:6px;height:6px;background:var(--primary,#0ABAB5);border-radius:50%;animation:cbB 1.4s ease-in-out infinite}
.cb-typing span:nth-child(2){animation-delay:.2s}
.cb-typing span:nth-child(3){animation-delay:.4s}
@keyframes cbB{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-5px)}}
.cb-quick{display:flex;flex-wrap:wrap;gap:6px;padding:6px 14px 10px;background:#f5f5f5}
.cb-quick-btn{padding:5px 11px;background:#fff;border:1px solid #ddd;border-radius:20px;font-size:12px;color:#555;cursor:pointer;transition:all .2s;white-space:nowrap}
.cb-quick-btn:hover{background:var(--primary,#0ABAB5);color:#fff;border-color:var(--primary,#0ABAB5)}
.cb-input-area{padding:10px 14px;border-top:1px solid #eee;display:flex;gap:8px;align-items:center;background:#fff;flex-shrink:0}
.cb-input{flex:1;padding:9px 14px;border:1px solid #ddd;border-radius:24px;font-size:13.5px;outline:0;transition:border-color .2s;font-family:inherit;resize:none;max-height:70px;line-height:1.4}
.cb-input:focus{border-color:var(--primary,#0ABAB5)}
.cb-input::placeholder{color:#aaa}
.cb-send{width:38px;height:38px;background:linear-gradient(135deg,var(--primary,#0ABAB5),var(--primary-dark,#089E9A));border:none;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:transform .2s;flex-shrink:0;color:#fff;font-size:16px}
.cb-send:hover{transform:scale(1.05)}
@media(max-width:480px){.chatbot-window{width:calc(100% - 20px);height:calc(100% - 100px);bottom:90px;right:10px;left:10px;border-radius:12px}}
</style>

<div class="chatbot-window" id="chatbotWindow">
    <div class="cb-header">
        <div class="cb-header-icon"><i class="fas fa-robot"></i></div>
        <div class="cb-header-info"><h4>Manzeli AI</h4><span>Powered by AI • Ask anything</span></div>
        <button class="cb-close" id="cbClose">&times;</button>
    </div>
    <div class="cb-messages" id="cbMessages">
        <div class="cb-typing" id="cbTyping"><span></span><span></span><span></span></div>
    </div>
    <div class="cb-quick" id="cbQuick">
        <button class="cb-quick-btn" data-msg="Show me apartments under $500">🔍 Under $500</button>
        <button class="cb-quick-btn" data-msg="How do I rent a property?">📅 Rent</button>
        <button class="cb-quick-btn" data-msg="How do I buy a property?">💰 Buy</button>
        <button class="cb-quick-btn" data-msg="How do I list my property?">🏡 List</button>
        <button class="cb-quick-btn" data-msg="What can you help me with?">❓ Help</button>
    </div>
    <div class="cb-input-area">
        <textarea class="cb-input" id="cbInput" placeholder='Ask me anything about Manzeli...' rows="1"></textarea>
        <button class="cb-send" id="cbSend"><i class="fas fa-paper-plane"></i></button>
    </div>
</div>

<script>
(function(){
    var btn=document.getElementById('chatbotBtn'),win=document.getElementById('chatbotWindow'),
        cls=document.getElementById('cbClose'),inp=document.getElementById('cbInput'),
        snd=document.getElementById('cbSend'),msgs=document.getElementById('cbMessages'),
        typ=document.getElementById('cbTyping'),qck=document.getElementById('cbQuick'),
        qbs=document.querySelectorAll('.cb-quick-btn'),isOpen=false,greeted=false,loading=false;

    if(!btn)return;

    btn.addEventListener('click',function(){
        isOpen=!isOpen;
        win.classList.toggle('open',isOpen);
        if(isOpen){
            inp.focus();
            if(!greeted){
                addBot("مرحبا! Welcome to Manzeli 🏠<br><br>I'm your <b>AI assistant</b> powered by advanced AI. I can:<br>• 🔍 <b>Search properties</b> from our database<br>• 💬 Answer questions about the platform<br>• 🏡 Help you rent, buy, or list properties<br><br>Try asking me anything!");
                greeted=true;
            }
        }
    });

    cls.addEventListener('click',function(){isOpen=false;win.classList.remove('open');});

    snd.addEventListener('click',send);
    inp.addEventListener('keydown',function(e){if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();send();}});
    inp.addEventListener('input',function(){this.style.height='auto';this.style.height=Math.min(this.scrollHeight,70)+'px';});
    qbs.forEach(function(b){b.addEventListener('click',function(){inp.value=this.getAttribute('data-msg');send();});});

    function send(){
        var msg=inp.value.trim();
        if(!msg||loading)return;
        addUser(msg);inp.value='';inp.style.height='auto';
        if(qck)qck.style.display='none';
        loading=true;typ.classList.add('show');scroll();

        var fd=new FormData();fd.append('chatbot_query',msg);
        fetch('/includes/chatbot-widget.php',{method:'POST',body:fd})
        .then(function(r){return r.json();})
        .then(function(d){
            typ.classList.remove('show');
            addBot(d.message);
            if(d.type==='properties'&&d.properties&&d.properties.length>0){
                d.properties.forEach(function(p){addCard(p);});
            }
        })
        .catch(function(){typ.classList.remove('show');addBot("Sorry, something went wrong. Please try again.");})
        .finally(function(){loading=false;inp.focus();});
    }

    function addUser(t){var d=document.createElement('div');d.className='cb-msg user';d.textContent=t;msgs.insertBefore(d,typ);scroll();}
    function addBot(t){var d=document.createElement('div');d.className='cb-msg bot';d.innerHTML=formatMsg(t);msgs.insertBefore(d,typ);scroll();}

    function formatMsg(t){
        return t.replace(/\*\*(.*?)\*\*/g,'<b>$1</b>').replace(/\n/g,'<br>');
    }

    function addCard(p){
        var c=document.createElement('div');c.className='cb-prop';
        var img=p.image||'/assets/images/placeholder.jpg';
        var pr='$'+Number(p.price).toLocaleString();
        var pd=(p.listing_type==='rent'&&p.price_period)?'<span>/'+p.price_period+'</span>':'';
        var dt=[];
        if(p.bedrooms)dt.push(p.bedrooms+' bed');
        if(p.bathrooms)dt.push(p.bathrooms+' bath');
        if(p.area)dt.push(p.area+' m²');
        var info=dt.length?dt.join(' • '):p.listing_type;
        c.innerHTML='<img src="'+img+'" onerror="this.src=\'/assets/images/placeholder.jpg\'">'+
            '<div class="cb-prop-body">'+
            '<p class="cb-prop-title">'+(p.title||'Property')+'</p>'+
            '<p class="cb-prop-info">📍 '+(p.location||'Lebanon')+' • '+info+'</p>'+
            '<div class="cb-prop-price">'+pr+pd+'</div>'+
            '<a href="/pages/property.php?id='+p.id+'" class="cb-prop-link">View Details →</a></div>';
        msgs.insertBefore(c,typ);scroll();
    }

    function scroll(){msgs.scrollTop=msgs.scrollHeight;}
})();
</script>
