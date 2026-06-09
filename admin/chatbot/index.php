<?php
// chatbot/index.php - Advanced PropertySync AI Chatbot
session_start();
require_once '../config.php';
header('Content-Type: application/json');

// Initialize conversation history
if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}

// Get user input
$input = trim($_POST['message'] ?? '');
$input_lower = strtolower($input);

// Add to chat history
$_SESSION['chat_history'][] = ['user' => $input, 'time' => time()];

$response = '';
$link = '';

// --- FETCH ALL BOT RESPONSES FROM DATABASE ---
$bot_responses = [];
$result = mysqli_query($conn, "SELECT * FROM bot_responses ORDER BY priority DESC, category, id");
while ($row = mysqli_fetch_assoc($result)) {
    $bot_responses[] = $row;
}

// --- INTELLIGENT PATTERN MATCHING ---
$matched = false;

// 1. Check database patterns first (with keyword scoring)
$best_match = null;
$best_score = 0;

foreach ($bot_responses as $bot) {
    $patterns = explode('|', strtolower($bot['pattern']));
    $score = 0;
    
    foreach ($patterns as $pattern) {
        $pattern = trim($pattern);
        if (strpos($input_lower, $pattern) !== false) {
            // Calculate match quality
            $score += strlen($pattern) * 10; // Longer patterns get higher priority
            if ($input_lower === $pattern) {
                $score += 100; // Exact match bonus
            }
        }
    }
    
    if ($score > $best_score) {
        $best_score = $score;
        $best_match = $bot;
    }
}

if ($best_match && $best_score > 0) {
    $response = $best_match['response'];
    $matched = true;
}

// --- 2. DYNAMIC PROPERTY SEARCH DETECTION ---
if (!$matched) {
    // Property types
    $property_types = [
        'house' => ['house', 'houses', 'home', 'homes', 'bungalow', 'duplex', 'villa'],
        'apartment' => ['apartment', 'apartments', 'flat', 'flats', 'condo'],
        'office' => ['office', 'offices', 'workspace', 'commercial space'],
        'land' => ['land', 'plot', 'plots', 'acre', 'acres'],
        'shop' => ['shop', 'shops', 'store', 'stores', 'retail space']
    ];
    
    // Nigerian states and major cities
    $locations = [
        'lagos' => ['lagos', 'lekki', 'ikoyi', 'victoria island', 'ikeja', 'surulere', 'yaba', 'ajah', 'vi'],
        'abuja' => ['abuja', 'fct', 'gwarinpa', 'wuse', 'asokoro', 'maitama', 'jabi', 'garki'],
        'port harcourt' => ['port harcourt', 'ph', 'rivers'],
        'ibadan' => ['ibadan', 'oyo'],
        'kano' => ['kano'],
        'enugu' => ['enugu'],
        'kaduna' => ['kaduna'],
        'benin' => ['benin', 'edo'],
        'calabar' => ['calabar', 'cross river'],
        'owerri' => ['owerri', 'imo']
    ];
    
    // Transaction types
    $transaction_keywords = [
        'rent' => ['rent', 'rental', 'lease', 'leasing', 'tenant'],
        'buy' => ['buy', 'purchase', 'sale', 'buying', 'own', 'invest']
    ];
    
    $detected_type = null;
    $detected_location = null;
    $detected_transaction = 'buy'; // Default
    
    // Detect property type
    foreach ($property_types as $type => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($input_lower, $keyword) !== false) {
                $detected_type = $type;
                break 2;
            }
        }
    }
    
    // Detect location
    foreach ($locations as $state => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($input_lower, $keyword) !== false) {
                $detected_location = $state;
                break 2;
            }
        }
    }
    
    // Detect transaction type
    foreach ($transaction_keywords as $trans => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($input_lower, $keyword) !== false) {
                $detected_transaction = $trans;
                break 2;
            }
        }
    }
    
    // Generate response if we detected something
    if ($detected_type || $detected_location) {
        $search_params = [];
        
        if ($detected_type) {
            $search_params[] = "type=" . urlencode($detected_type);
        }
        if ($detected_location) {
            $search_params[] = "state=" . urlencode($detected_location);
        }
        if ($detected_transaction) {
            $search_params[] = "stype=" . urlencode($detected_transaction);
        }
        
        $search_link = "../propertygrid.php?" . implode('&', $search_params);
        
        $type_text = $detected_type ? ucfirst($detected_type) . 's' : 'Properties';
        $location_text = $detected_location ? ' in ' . ucfirst(str_replace('_', ' ', $detected_location)) : '';
        $trans_text = $detected_transaction === 'rent' ? 'for Rent' : 'for Sale';
        
        $response = "Great! I found {$type_text}{$location_text} {$trans_text}. Let me show you what's available.";
        $link = $search_link;
        $matched = true;
    }
}

// --- 3. CONTEXTUAL UNDERSTANDING ---
if (!$matched) {
    // Intent detection
    $intents = [
        'greeting' => ['hi', 'hello', 'hey', 'good morning', 'good afternoon', 'good evening', 'greetings'],
        'help' => ['help', 'assist', 'support', 'guide', 'how to', 'how do i', 'show me'],
        'browse' => ['show', 'view', 'see', 'browse', 'list', 'available', 'what do you have'],
        'price' => ['price', 'cost', 'expensive', 'cheap', 'affordable', 'budget', 'how much'],
        'contact' => ['contact', 'call', 'email', 'reach', 'talk to', 'speak with'],
        'account' => ['register', 'signup', 'sign up', 'account', 'profile', 'login', 'log in'],
        'sell' => ['sell', 'list my property', 'add property', 'advertise'],
        'features' => ['amenities', 'features', 'facilities', 'bedrooms', 'bathrooms']
    ];
    
    foreach ($intents as $intent => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($input_lower, $keyword) !== false) {
                switch ($intent) {
                    case 'greeting':
                        $response = "Hello! 👋 Welcome to PropertySync. I'm here to help you find your perfect property. What are you looking for today?";
                        $matched = true;
                        break 2;
                        
                    case 'help':
                        $response = "I can help you with:<br>
                        • Finding properties to buy or rent<br>
                        • Searching by location (e.g., 'houses in Lagos')<br>
                        • Filtering by price, bedrooms, features<br>
                        • Contacting property owners<br>
                        • Creating an account<br>
                        <br>Just tell me what you need!";
                        $matched = true;
                        break 2;
                        
                    case 'browse':
                        $response = "I'd love to show you our available properties! What are you interested in?";
                        $link = "../propertygrid.php";
                        $matched = true;
                        break 2;
                        
                    case 'price':
                        $response = "Property prices vary by location and type. You can filter by your budget when searching. What's your price range and preferred location?";
                        $matched = true;
                        break 2;
                        
                    case 'contact':
                        $response = "Need assistance? You can:<br>
                        • Call us: +234-XXX-XXXX<br>
                        • Email: info@propertysync.com<br>
                        • Or use the contact form";
                        $link = "../contact.php";
                        $matched = true;
                        break 2;
                        
                    case 'account':
                        $response = "Creating an account lets you save favorites, contact owners, and list properties. Ready to sign up?";
                        $link = "../register.php";
                        $matched = true;
                        break 2;
                        
                    case 'sell':
                        $response = "Want to list your property? You'll need an account first. I can guide you through the process!";
                        $link = "../submitproperty.php";
                        $matched = true;
                        break 2;
                        
                    case 'features':
                        $response = "You can filter properties by bedrooms, bathrooms, parking, and more. What specific features are you looking for?";
                        $matched = true;
                        break 2;
                }
            }
        }
    }
}

// --- 4. QUESTION DETECTION ---
if (!$matched && (strpos($input_lower, '?') !== false || 
    strpos($input_lower, 'what') !== false || 
    strpos($input_lower, 'how') !== false ||
    strpos($input_lower, 'where') !== false ||
    strpos($input_lower, 'when') !== false ||
    strpos($input_lower, 'why') !== false)) {
    
    $response = "That's a great question! I'm here to help you find properties. Could you tell me:<br>
    • What type of property? (house, apartment, office, land)<br>
    • Which location? (Lagos, Abuja, etc.)<br>
    • To buy or rent?";
    $matched = true;
}

// --- 5. FALLBACK WITH SUGGESTIONS ---
if (!$matched || empty($response)) {
    $suggestions = [
        "I want to help you find the perfect property! Try asking:",
        "• 'I need a house in Lagos'",
        "• 'Show me apartments for rent in Abuja'",
        "• 'What properties are available?'",
        "• 'Help me buy a home'",
        "",
        "Or browse all properties below:"
    ];
    $response = implode("<br>", $suggestions);
    $link = "../propertygrid.php";
}

// --- BUILD RESPONSE WITH LINK ---
$final_response = $response;
if ($link) {
    $final_response .= "<br><br><a href=\"{$link}\" target=\"_blank\" class=\"chat-link\">👉 Click here to continue</a>";
}

// Return JSON response
echo json_encode([
    'response' => $final_response,
    'timestamp' => date('g:i A')
]);

// --- CLEANUP OLD SESSIONS ---
if (count($_SESSION['chat_history']) > 50) {
    $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -20);
}
?>