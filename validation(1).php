<?php
/**
 * FALCONS Theater - Input Validation Rules
 * Centralized validation rules for form data
 */

class ValidationRules {
    
    /**
     * Validate booking data
     */
    public static function validateBooking($data) {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        } elseif (strlen($data['name']) < 3) {
            $errors['name'] = 'Name must be at least 3 characters';
        } elseif (strlen($data['name']) > 100) {
            $errors['name'] = 'Name cannot exceed 100 characters';
        }
        
        if (empty($data['phone'])) {
            $errors['phone'] = 'Phone number is required';
        } elseif (!validatePhone($data['phone'])) {
            $errors['phone'] = 'Phone number is invalid';
        }
        
        if (empty($data['seats']) || !is_array($data['seats'])) {
            $errors['seats'] = 'Please select at least one seat';
        } elseif (!validateSeatNumbers($data['seats'])) {
            $errors['seats'] = 'Invalid seat selection';
        }
        
        if (empty($data['movie'])) {
            $errors['movie'] = 'Movie is required';
        } elseif (!validateMovieName($data['movie'])) {
            $errors['movie'] = 'Invalid movie name';
        }
        
        if (empty($data['totalAmount'])) {
            $errors['totalAmount'] = 'Amount is required';
        } elseif (!validateAmount($data['totalAmount'])) {
            $errors['totalAmount'] = 'Invalid amount';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate payment data
     */
    public static function validatePayment($data) {
        $errors = [];
        
        $bookingId = intval($data['booking_id'] ?? 0);
        if ($bookingId <= 0) {
            $errors['booking_id'] = 'Invalid booking ID';
        }
        
        $amount = floatval($data['cash_given'] ?? $data['amount'] ?? 0);
        if ($amount <= 0) {
            $errors['amount'] = 'Amount must be greater than 0';
        } elseif ($amount > 999999.99) {
            $errors['amount'] = 'Amount exceeds maximum limit';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate card data
     */
    public static function validateCardData($data) {
        $errors = [];
        
        if (empty($data['card_name'])) {
            $errors['card_name'] = 'Card holder name is required';
        } elseif (strlen($data['card_name']) < 3) {
            $errors['card_name'] = 'Name must be at least 3 characters';
        }
        
        $cardNumber = preg_replace('/\D/', '', $data['card_number'] ?? '');
        if (strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
            $errors['card_number'] = 'Invalid card number';
        } elseif (!self::luhnCheck($cardNumber)) {
            $errors['card_number'] = 'Card number failed validation';
        }
        
        $expiry = trim($data['expiry'] ?? '');
        if (empty($expiry) || !preg_match('/^\d{2}\/\d{2}$/', $expiry)) {
            $errors['expiry'] = 'Expiry must be in MM/YY format';
        } else {
            list($month, $year) = explode('/', $expiry);
            $currentYear = intval(date('y'));
            $currentMonth = intval(date('m'));
            $expYear = intval($year);
            $expMonth = intval($month);
            
            if ($expYear < $currentYear || ($expYear === $currentYear && $expMonth < $currentMonth)) {
                $errors['expiry'] = 'Card has expired';
            }
        }
        
        $cvv = preg_replace('/\D/', '', $data['cvv'] ?? '');
        if (strlen($cvv) < 3 || strlen($cvv) > 4) {
            $errors['cvv'] = 'CVV must be 3-4 digits';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate movie data
     */
    public static function validateMovie($data) {
        $errors = [];
        
        if (empty($data['title'])) {
            $errors['title'] = 'Movie title is required';
        } elseif (strlen($data['title']) < 2 || strlen($data['title']) > 200) {
            $errors['title'] = 'Movie title must be between 2 and 200 characters';
        }
        
        if (empty($data['language'])) {
            $errors['language'] = 'Language is required';
        }
        
        if (empty($data['description'])) {
            $errors['description'] = 'Description is required';
        } elseif (strlen($data['description']) < 10) {
            $errors['description'] = 'Description must be at least 10 characters';
        }
        
        if (!empty($data['rating'])) {
            $rating = floatval($data['rating']);
            if ($rating < 0 || $rating > 10) {
                $errors['rating'] = 'Rating must be between 0 and 10';
            }
        }
        
        if (!empty($data['release_date'])) {
            if (!strtotime($data['release_date'])) {
                $errors['release_date'] = 'Invalid release date';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate user profile data
     */
    public static function validateUserProfile($data) {
        $errors = [];
        
        if (empty($data['username'])) {
            $errors['username'] = 'Username is required';
        } elseif (strlen($data['username']) < 3) {
            $errors['username'] = 'Username must be at least 3 characters';
        } elseif (strlen($data['username']) > 50) {
            $errors['username'] = 'Username cannot exceed 50 characters';
        }
        
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!validateEmail($data['email'])) {
            $errors['email'] = 'Invalid email format';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate contact message
     */
    public static function validateContactMessage($data) {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        } elseif (strlen($data['name']) < 2) {
            $errors['name'] = 'Name must be at least 2 characters';
        }
        
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!validateEmail($data['email'])) {
            $errors['email'] = 'Invalid email format';
        }
        
        if (empty($data['message'])) {
            $errors['message'] = 'Message is required';
        } elseif (strlen($data['message']) < 10) {
            $errors['message'] = 'Message must be at least 10 characters';
        } elseif (strlen($data['message']) > 1000) {
            $errors['message'] = 'Message cannot exceed 1000 characters';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Luhn algorithm for card validation
     */
    private static function luhnCheck($cardNumber) {
        $digits = str_split($cardNumber);
        $sum = 0;
        $isEven = false;
        
        for ($i = count($digits) - 1; $i >= 0; $i--) {
            $digit = intval($digits[$i]);
            
            if ($isEven) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            
            $sum += $digit;
            $isEven = !$isEven;
        }
        
        return ($sum % 10) === 0;
    }
}

/**
 * Helper function to get first error from validation result
 */
function getFirstError($validation) {
    if (!$validation['valid'] && !empty($validation['errors'])) {
        return array_values($validation['errors'])[0];
    }
    return '';
}

/**
 * Helper function to get all errors as HTML
 */
function getErrorsHTML($validation) {
    if (!$validation['valid'] && !empty($validation['errors'])) {
        $html = '<ul class="errors">';
        foreach ($validation['errors'] as $field => $error) {
            $html .= '<li><strong>' . htmlspecialchars(ucfirst(str_replace('_', ' ', $field))) . ':</strong> ' . htmlspecialchars($error) . '</li>';
        }
        $html .= '</ul>';
        return $html;
    }
    return '';
}

?>
