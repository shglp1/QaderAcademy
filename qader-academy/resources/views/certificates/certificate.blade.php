<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('messages.certificate_title') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .certificate-container {
            background: white;
            width: 100%;
            max-width: 900px;
            padding: 50px;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
        }
        
        .certificate-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(102,126,234,0.1) 0%, transparent 70%);
            pointer-events: none;
        }
        
        .border-frame {
            border: 8px solid #667eea;
            border-radius: 5px;
            padding: 40px;
            position: relative;
            z-index: 1;
        }
        
        .corner-decoration {
            position: absolute;
            width: 80px;
            height: 80px;
            border: 4px solid #764ba2;
        }
        
        .top-left {
            top: 10px;
            left: 10px;
            border-right: none;
            border-bottom: none;
        }
        
        .top-right {
            top: 10px;
            right: 10px;
            border-left: none;
            border-bottom: none;
        }
        
        .bottom-left {
            bottom: 10px;
            left: 10px;
            border-right: none;
            border-top: none;
        }
        
        .bottom-right {
            bottom: 10px;
            right: 10px;
            border-left: none;
            border-top: none;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        h1 {
            font-size: 42px;
            color: #333;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 3px;
        }
        
        .subtitle {
            font-size: 18px;
            color: #666;
            font-style: italic;
        }
        
        .content {
            text-align: center;
            margin: 40px 0;
        }
        
        .present-line {
            font-size: 16px;
            color: #666;
            margin-bottom: 20px;
        }
        
        .student-name {
            font-size: 36px;
            color: #667eea;
            font-weight: bold;
            margin: 20px 0;
            padding: 15px;
            border-bottom: 3px solid #667eea;
            display: inline-block;
            min-width: 400px;
        }
        
        .achievement-text {
            font-size: 16px;
            color: #555;
            line-height: 1.8;
            margin: 30px 0;
        }
        
        .course-title {
            font-size: 28px;
            color: #764ba2;
            font-weight: bold;
            margin: 20px 0;
            padding: 15px;
            background: linear-gradient(135deg, rgba(102,126,234,0.1) 0%, rgba(118,75,162,0.1) 100%);
            border-radius: 5px;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 40px 0;
            text-align: center;
        }
        
        .detail-item {
            padding: 15px;
            background: rgba(102,126,234,0.05);
            border-radius: 5px;
        }
        
        .detail-label {
            font-size: 12px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        
        .detail-value {
            font-size: 16px;
            color: #333;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 2px solid #eee;
            padding-top: 30px;
        }
        
        .signature-section {
            text-align: center;
        }
        
        .signature-line {
            width: 200px;
            border-top: 2px solid #333;
            margin: 10px auto;
        }
        
        .signature-label {
            font-size: 14px;
            color: #666;
            margin-top: 10px;
        }
        
        .verification-section {
            text-align: center;
            padding: 15px;
            background: rgba(118,75,162,0.1);
            border-radius: 5px;
            min-width: 250px;
        }
        
        .verification-code {
            font-family: 'Courier New', monospace;
            font-size: 14px;
            color: #764ba2;
            font-weight: bold;
            letter-spacing: 2px;
            margin-top: 5px;
        }
        
        .verification-label {
            font-size: 11px;
            color: #888;
            text-transform: uppercase;
        }
        
        [dir="rtl"] {
            direction: rtl;
        }
        
        [dir="rtl"] .student-name {
            font-family: 'Tahoma', 'Arial', sans-serif;
        }
        
        [dir="rtl"] h1 {
            font-family: 'Tahoma', 'Arial', sans-serif;
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="border-frame">
            <!-- Corner decorations -->
            <div class="corner-decoration top-left"></div>
            <div class="corner-decoration top-right"></div>
            <div class="corner-decoration bottom-left"></div>
            <div class="corner-decoration bottom-right"></div>
            
            <!-- Header -->
            <div class="header">
                <div class="logo">🎓</div>
                <h1>{{ __('messages.certificate_of_completion') }}</h1>
                <p class="subtitle">{{ __('messages.this_certifies_that') }}</p>
            </div>
            
            <!-- Content -->
            <div class="content">
                <p class="present-line">{{ __('messages.this_is_to_certify_that') }}</p>
                
                <div class="student-name">{{ $student_name }}</div>
                
                <p class="achievement-text">
                    {{ __('messages.has_successfully_completed_the_course') }}
                </p>
                
                <div class="course-title">{{ $course_title }}</div>
                
                <p class="achievement-text">
                    {{ __('messages.trained_by') }} <strong>{{ $trainer_name }}</strong>
                </p>
                
                <!-- Details Grid -->
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">{{ __('messages.completion_date') }}</div>
                        <div class="detail-value">{{ \Carbon\Carbon::parse($completion_date)->format('F d, Y') }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">{{ __('messages.certificate_number') }}</div>
                        <div class="detail-value">{{ $certificate_number }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">{{ __('messages.issue_date') }}</div>
                        <div class="detail-value">{{ \Carbon\Carbon::now()->format('F d, Y') }}</div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="footer">
                <div class="signature-section">
                    <div class="signature-line"></div>
                    <div class="signature-label">{{ __('messages.trainer_signature') }}</div>
                </div>
                
                <div class="verification-section">
                    <div class="verification-label">{{ __('messages.verify_at') }}</div>
                    <div class="verification-code">{{ $verification_code }}</div>
                </div>
                
                <div class="signature-section">
                    <div class="signature-line"></div>
                    <div class="signature-label">{{ __('messages.administration') }}</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
