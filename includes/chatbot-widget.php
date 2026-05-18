<?php
// ============================================================
// MANZELI CHATBOT — Groq AI + Smart Database Search
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
    
    // Check if asking about properties/database
    $dbResult = searchProperties($pdo, $lower, $message);
    if ($dbResult !== null) {
        echo json_encode($dbResult);
        exit;
    }
    
    // Check if asking for property details by ID or name
    $detailResult = getPropertyDetails($pdo, $lower, $message);
    if ($detailResult !== null) {
        echo json_encode($detailResult);
        exit;
    }
    
    // Get database stats to feed the AI
    $dbStats = getDatabaseStats($pdo);
    
    // Use Groq AI with database context
    $aiResponse = callGroqAPI($message, $dbStats);
    echo json_encode(['type' => 'text', 'message' => $aiResponse]);
    exit;
}

// ============================================================
// DATABASE STATS — Feed to AI so it knows what's in the DB
// ============================================================
function getDatabaseStats($pdo) {
    try {
        $stats = [];
        $stats['total_properties'] = $pdo->query("SELECT COUNT(*) FROM properties WHERE status='active'")->fetchColumn();
        $stats['rent_count'] = $pdo->query("SELECT COUNT(*) FROM properties WHERE status='active' AND listing_type='rent'")->fetchColumn();
        $stats['buy_count'] = $pdo->query("SELECT COUNT(*) FROM properties WHERE status='active' AND listing_type='buy'")->fetchColumn();
        $stats['land_count'] = $pdo->query("SELECT COUNT(*) FROM properties WHERE status='active' AND listing_type='land'")->fetchColumn();
        $stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $stats['total_reviews'] = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
        
        // Get recent properties summary
        $recent = $pdo->query("SELECT title, listing_type, price, price_period, location, bedrooms, bathrooms FROM properties WHERE status='active' ORDER BY created_at DESC LIMIT 10")->fetchAll();
        $stats['recent_properties'] = $recent;
        
        // Get locations
        $locations = $pdo->query("SELECT DISTINCT location FROM properties WHERE status='active' AND location IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
        $stats['locations'] = $locations;
        
        return $stats;
    } catch (Exception $e) {
        return ['total_properties' => 0];
    }
}

// ============================================================
// PROPERTY DETAILS — Get details of a specific property
// ============================================================
function getPropertyDetails($pdo, $lower, $original) {
    // Check for property ID
    if (preg_match('/property\s*#?\s*(\d+)|id\s*(\d+)|details?\s*(?:of|about|for)?\s*#?\s*(\d+)/i', $original, $m)) {
        $propId = (int)($m[1] ?: ($m[2] ?: $m[3]));
        try {
            $stmt = $pdo->prepare("SELECT p.*, (SELECT GROUP_CONCAT(a.name SEPARATOR ', ') FROM property_amenities pa JOIN amenities a ON pa.amenity_id = a.id WHERE pa.property_id = p.id) as amenities_list, (SELECT ROUND(AVG(rating),1) FROM reviews WHERE property_id = p.id) as avg_rating, (SELECT COUNT(*) FROM reviews WHERE property_id = p.id) as review_count, (SELECT image_path FROM property_images WHERE property_id = p.id AND is_main = 1 LIMIT 1) as image FROM properties p WHERE p.id = ?");
            $stmt->execute([$propId]);
            $prop = $stmt->fetch();
            
            if ($prop) {
                $details = "📋 <b>" . htmlspecialchars($prop['title']) . "</b><br><br>";
                $details .= "📍 Location: " . htmlspecialchars($prop['location']) . "<br>";
                $details .= "🏷️ Type: " . ucfirst($prop['listing_type']) . "<br>";
                $details .= "💰 Price: $" . number_format($prop['price']);
                if ($prop['listing_type'] === 'rent') $details .= " / " . ($prop['price_period'] ?? 'night');
                $details .= "<br>";
                if ($prop['bedrooms']) $details .= "🛏️ Bedrooms: " . $prop['bedrooms'] . "<br>";
                if ($prop['bathrooms']) $details .= "🚿 Bathrooms: " . $prop['bathrooms'] . "<br>";
                if ($prop['area_sqm']) $details .= "📐 Area: " . $prop['area_sqm'] . " m²<br>";
                if ($prop['amenities_list']) $details .= "✨ Amenities: " . $prop['amenities_list'] . "<br>";
                if ($prop['avg_rating']) $details .= "⭐ Rating: " . $prop['avg_rating'] . "/5 (" . $prop['review_count'] . " reviews)<br>";
                if ($prop['description']) $details .= "<br>" . htmlspecialchars(substr($prop['description'], 0, 200));
                
                $properties = [['id' => $prop['id'], 'title' => $prop['title'], 'price' => $prop['price'], 'price_period' => $prop['price_period'], 'listing_type' => $prop['listing_type'], 'location' => $prop['location'], 'bedrooms' => $prop['bedrooms'], 'bathrooms' => $prop['bathrooms'], 'area' => $prop['area_sqm'], 'image' => $prop['image']]];
                
                return ['type' => 'properties', 'message' => $details, 'properties' => $properties];
            }
        } catch (Exception $e) {}
    }
    
    // Check for property by name
    $nameKeywords = ['details', 'about', 'tell me about', 'info about', 'information about', 'describe'];
    $isDetailQuery = false;
    foreach ($nameKeywords as $kw) {
        if (strpos($lower, $kw) !== false) { $isDetailQuery = true; break; }
    }
    
    if ($isDetailQuery) {
        // Try to find property by title match
        try {
            $searchTerm = preg_replace('/^(details?|about|tell me about|info about|information about|describe)\s*(of|about|for)?\s*/i', '', $original);
            $searchTerm = trim($searchTerm);
            if (strlen($searchTerm) > 2) {
                $stmt = $pdo->prepare("SELECT id, title, price, price_period, listing_type, location, bedrooms, bathrooms, area_sqm as area, (SELECT image_path FROM property_images WHERE property_id = p.id AND is_main = 1 LIMIT 1) as image FROM properties p WHERE status='active' AND LOWER(title) LIKE ? LIMIT 3");
                $stmt->execute(['%' . strtolower($searchTerm) . '%']);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($results)) {
                    $count = count($results);
                    return ['type' => 'properties', 'message' => "I found {$count} matching " . ($count === 1 ? 'property' : 'properties') . ":", 'properties' => $results];
                }
            }
        } catch (Exception $e) {}
    }
    
    return null;
}

// ============================================================
// DATABASE SEARCH — Much broader triggers
// ============================================================
function searchProperties($pdo, $lower, $original) {
    $propertyTriggers = ['show me', 'find me', 'search', 'looking for', 'i want', 'i need', 'available',
        'apartment', 'house', 'villa', 'studio', 'chalet', 'property', 'properties',
        'land', 'plot', 'terrain',
        'cheap', 'expensive', 'affordable',
        'under $', 'less than', 'below', 'above', 'over', 'more than',
        'how many properties', 'how many listings', 'what properties', 'list all',
        'bedroom', 'bedrooms', 'bed ', 'beds',
        'rent in', 'buy in', 'land in', 'for rent', 'for sale',
        'beirut', 'jounieh', 'batroun', 'byblos', 'tripoli', 'sidon', 'zahle', 'jbeil',
        'keserwan', 'metn', 'chouf', 'baabda', 'aley', 'broummana', 'dbayeh',
        'antelias', 'hamra', 'achrafieh', 'verdun', 'rabieh', 'hazmieh', 'tyre',
        'nabatieh', 'baalbek', 'sohmor', 'bekaa', 'beqaa',
        'all properties', 'all listings', 'everything available', 'what do you have',
        'any properties', 'any apartments', 'any houses', 'any land'];
    
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
    
    if (strpos($lower, 'rent') !== false || strpos($lower, 'for rent') !== false) {
        $conditions[] = "p.listing_type = 'rent'";
    } elseif (strpos($lower, 'buy') !== false || strpos($lower, 'sale') !== false || strpos($lower, 'purchase') !== false) {
        $conditions[] = "p.listing_type = 'buy'";
    } elseif (strpos($lower, 'land') !== false || strpos($lower, 'plot') !== false || strpos($lower, 'terrain') !== false) {
        $conditions[] = "p.listing_type = 'land'";
    }
    
    if (preg_match('/(?:under|less than|below|cheaper than|max|up to)\s*\$?\s*(\d[\d,]*)/i', $original, $m)) {
        $conditions[] = "p.price <= ?"; $params[] = (int)str_replace(',', '', $m[1]);
    }
    if (preg_match('/(?:over|above|more than|at least|min)\s*\$?\s*(\d[\d,]*)/i', $original, $m)) {
        $conditions[] = "p.price >= ?"; $params[] = (int)str_replace(',', '', $m[1]);
    }
    if (preg_match('/between\s*\$?\s*(\d[\d,]*)\s*(?:and|to|-)\s*\$?\s*(\d[\d,]*)/i', $original, $m)) {
        $conditions[] = "p.price >= ? AND p.price <= ?";
        $params[] = (int)str_replace(',', '', $m[1]);
        $params[] = (int)str_replace(',', '', $m[2]);
    }
    
    if (preg_match('/(\d+)\s*(?:bed|bedroom|bedrooms|beds|br|room)/i', $original, $m)) {
        $conditions[] = "p.bedrooms = ?"; $params[] = (int)$m[1];
    }
    
    $locations = ['beirut','jounieh','batroun','byblos','jbeil','tripoli','sidon','saida','zahle','keserwan','metn','chouf','baabda','aley','broummana','dbayeh','antelias','hamra','achrafieh','verdun','rabieh','hazmieh','tyre','sour','nabatieh','baalbek','zgharta','koura','kaslik','tabarja','sohmor','bekaa','beqaa'];
    foreach ($locations as $loc) {
        if (strpos($lower, $loc) !== false) {
            $conditions[] = "LOWER(p.location) LIKE ?";
            $params[] = '%' . $loc . '%';
            break;
        }
    }
    
    $where = implode(' AND ', $conditions);
    $sql = "SELECT p.id, p.title, p.price, p.price_period, p.listing_type, p.location, p.bedrooms, p.bathrooms, p.area_sqm as area, (SELECT image_path FROM property_images WHERE property_id = p.id AND is_main = 1 LIMIT 1) as image FROM properties p WHERE {$where} ORDER BY p.created_at DESC LIMIT 5";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return ['type' => 'text', 'message' => "I'm connected to the database but encountered an error. Please try a different search."];
    }
    
    if (empty($properties)) {
        // Check total count
        $total = $pdo->query("SELECT COUNT(*) FROM properties WHERE status='active'")->fetchColumn();
        if ($total == 0) {
            return ['type' => 'text', 'message' => "I'm connected to the database, but there are <b>no properties listed yet</b>. Properties will appear here once hosts start adding listings! 🏠<br><br>You can <a href='/pages/register.php' style='color:var(--primary);font-weight:600;'>register as a host</a> to add the first property."];
        } else {
            return ['type' => 'text', 'message' => "I searched the database but couldn't find properties matching your criteria. We currently have <b>{$total} active listings</b>. Try different filters or <a href='/pages/listings.php' style='color:var(--primary);font-weight:600;'>browse all listings</a>."];
        }
    }
    
    $count = count($properties);
    return ['type' => 'properties', 'message' => "I found <b>{$count}</b> " . ($count === 1 ? 'property' : 'properties') . " for you:", 'properties' => $properties];
}

// ============================================================
// GROQ API — With database context
// ============================================================
function callGroqAPI($userMessage, $dbStats) {
   $apiKey = getenv('GROQ_API_KEY');
    
    // Build database context for AI
    $dbContext = "\n\nCURRENT DATABASE STATUS (live data):\n";
    $dbContext .= "- Total active properties: " . ($dbStats['total_properties'] ?? 0) . "\n";
    $dbContext .= "- Rentals: " . ($dbStats['rent_count'] ?? 0) . "\n";
    $dbContext .= "- For sale: " . ($dbStats['buy_count'] ?? 0) . "\n";
    $dbContext .= "- Land: " . ($dbStats['land_count'] ?? 0) . "\n";
    $dbContext .= "- Total users: " . ($dbStats['total_users'] ?? 0) . "\n";
    $dbContext .= "- Total reviews: " . ($dbStats['total_reviews'] ?? 0) . "\n";
    
    if (!empty($dbStats['locations'])) {
        $dbContext .= "- Available locations: " . implode(', ', $dbStats['locations']) . "\n";
    }
    
    if (!empty($dbStats['recent_properties'])) {
        $dbContext .= "- Recent listings:\n";
        foreach ($dbStats['recent_properties'] as $p) {
            $dbContext .= "  * " . $p['title'] . " (" . $p['listing_type'] . ") - $" . number_format($p['price']) . " in " . $p['location'] . "\n";
        }
    }
    
    $systemPrompt = "You are Manzeli's AI assistant — a helpful, friendly chatbot for a Lebanon-based real estate platform called Manzeli (منزلي meaning 'My Home' in Arabic). Built by Bassim Kazouini as a Senior Project 2024-2025.

About Manzeli:
- Real estate platform for renting, buying properties, and purchasing land in Lebanon
- Three listing types: Rent (book with dates), Buy (contact seller form), Land (request info form)
- Users can browse listings, filter by type/location/price/bedrooms, save favorites, leave reviews
- Guests can book rentals (credit card or pay on arrival), send purchase inquiries
- Hosts can list properties for free with photos, amenities, and descriptions
- 15 amenities: WiFi, Parking, Pool, A/C, Generator, Sea View, Mountain View, Balcony, Garden, Gym, Elevator, Security, Furnished, Heating, Fireplace
- Platform covers all Lebanon: Beirut, Jounieh, Batroun, Byblos, Tripoli, Sidon, Zahle, and more
- Contact: info@manzeli.com | +961 70 322 369 | West Bekaa, Sohmor, Lebanon

IMPORTANT: You ARE connected to the Manzeli database. Never say you are not connected. Use the database info below to answer questions about properties, users, and stats accurately.
" . $dbContext . "

Guidelines:
- Keep responses concise (2-4 sentences when possible)
- Be warm, professional, and helpful
- Use the database stats above to answer questions about available properties
- If the user asks to search properties, tell them to try: 'Show me apartments in Beirut' or 'Find properties under \$500'
- Never say you don't have access to the database — you do
- You can use emojis sparingly 🏠";

    if (!isset($_SESSION['chat_history'])) {
        $_SESSION['chat_history'] = [];
    }
    
    $messages = [['role' => 'system', 'content' => $systemPrompt]];
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
        return "I'm temporarily unavailable. Please try again shortly.";
    }
    
    $reply = $decoded['choices'][0]['message']['content'] ?? "Sorry, I couldn't process that. Please try again.";
    
    $_SESSION['chat_history'][] = ['role' => 'user', 'content' => $userMessage];
    $_SESSION['chat_history'][] = ['role' => 'assistant', 'content' => $reply];
    if (count($_SESSION['chat_history']) > 20) {
        $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -20);
    }
    
    return $reply;
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
        <div class="cb-header-info"><h4>Manzeli AI</h4><span>Powered by AI • Connected to database</span></div>
        <button class="cb-close" id="cbClose">&times;</button>
    </div>
    <div class="cb-messages" id="cbMessages">
        <div class="cb-typing" id="cbTyping"><span></span><span></span><span></span></div>
    </div>
    <div class="cb-quick" id="cbQuick">
        <button class="cb-quick-btn" data-msg="What properties do you have?">🏠 All Properties</button>
        <button class="cb-quick-btn" data-msg="Show me apartments for rent">📅 Rentals</button>
        <button class="cb-quick-btn" data-msg="Show me properties for sale">💰 For Sale</button>
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
                addBot("مرحبا! Welcome to Manzeli 🏠<br><br>I'm your <b>AI assistant</b> connected to the Manzeli database. I can:<br>• 🔍 <b>Search properties</b> — \"Show me apartments in Beirut\"<br>• 📋 <b>Property details</b> — \"Details of property #1\"<br>• 💬 <b>Answer questions</b> about the platform<br>• 📊 <b>Database stats</b> — \"How many properties?\"<br><br>Try asking me anything!");
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
