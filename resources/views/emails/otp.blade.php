<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mã OTP khôi phục mật khẩu</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 5px 5px;
        }
        .otp-code {
            background-color: #007bff;
            color: white;
            font-size: 24px;
            font-weight: bold;
            padding: 15px 30px;
            text-align: center;
            border-radius: 5px;
            margin: 20px 0;
            letter-spacing: 3px;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Khôi phục mật khẩu</h1>
    </div>
    
    <div class="content">
        @if($userName)
            <p>Xin chào <strong>{{ $userName }}</strong>,</p>
        @else
            <p>Xin chào,</p>
        @endif
        
        <p>Bạn đã yêu cầu khôi phục mật khẩu cho tài khoản của mình. Vui lòng sử dụng mã OTP dưới đây để xác nhận:</p>
        
        <div class="otp-code">
            {{ $otp }}
        </div>
        
        <div class="warning">
            <strong>Lưu ý:</strong>
            <ul>
                <li>Mã OTP này có hiệu lực trong <strong>5 phút</strong></li>
                <li>Chỉ sử dụng được <strong>1 lần</strong></li>
                <li>Không chia sẻ mã này với bất kỳ ai</li>
            </ul>
        </div>
        
        <p>Nếu bạn không yêu cầu khôi phục mật khẩu, vui lòng bỏ qua email này.</p>
        
        <p>Trân trọng,<br>
        <strong>Đội ngũ hỗ trợ</strong></p>
    </div>
    
    <div class="footer">
        <p>Email này được gửi tự động, vui lòng không trả lời.</p>
    </div>
</body>
</html>
