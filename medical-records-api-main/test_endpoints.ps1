# PowerShell test script for medical records API

Write-Host "=== Testing Login ===" -ForegroundColor Green

$loginBody = @{
    username = "admin"
    password = "password123"
} | ConvertTo-Json

try {
    $loginResponse = Invoke-RestMethod -Uri "http://localhost:8000/api/v1/login" `
        -Method POST `
        -ContentType "application/json" `
        -Body $loginBody
    
    Write-Host "Login successful!" -ForegroundColor Green
    $token = $loginResponse.access_token
    
    if ($token) {
        Write-Host "Token received: $($token.Substring(0, [Math]::Min(50, $token.Length)))..." -ForegroundColor Yellow
        
        $headers = @{
            "Authorization" = "Bearer $token"
            "Content-Type" = "application/json"
        }
        
        Write-Host "`n=== Testing Available Patients ===" -ForegroundColor Green
        try {
            $patientsResponse = Invoke-RestMethod -Uri "http://localhost:8000/api/v1/users/available-patients?limit=5" `
                -Method GET `
                -Headers $headers
            
            Write-Host "Available patients response:" -ForegroundColor Green
            $patientsResponse | ConvertTo-Json -Depth 3
        } catch {
            Write-Host "Patients endpoint error: $($_.Exception.Message)" -ForegroundColor Red
        }
        
        Write-Host "`n=== Testing Available Doctors ===" -ForegroundColor Green
        try {
            $doctorsResponse = Invoke-RestMethod -Uri "http://localhost:8000/api/v1/users/available-doctors?limit=5" `
                -Method GET `
                -Headers $headers
            
            Write-Host "Available doctors response:" -ForegroundColor Green
            $doctorsResponse | ConvertTo-Json -Depth 3
        } catch {
            Write-Host "Doctors endpoint error: $($_.Exception.Message)" -ForegroundColor Red
        }
        
        Write-Host "`n=== Testing Available Staffs ===" -ForegroundColor Green
        try {
            $staffsResponse = Invoke-RestMethod -Uri "http://localhost:8000/api/v1/users/available-staffs?limit=5" `
                -Method GET `
                -Headers $headers
            
            Write-Host "Available staffs response:" -ForegroundColor Green
            $staffsResponse | ConvertTo-Json -Depth 3
        } catch {
            Write-Host "Staffs endpoint error: $($_.Exception.Message)" -ForegroundColor Red
        }
        
        Write-Host "`n=== Testing Password Change ===" -ForegroundColor Green
        # Test password change by updating a user (if we have any users)
        try {
            $usersResponse = Invoke-RestMethod -Uri "http://localhost:8000/api/v1/users?limit=1" `
                -Method GET `
                -Headers $headers
            
            if ($usersResponse.rows -and $usersResponse.rows.Count -gt 0) {
                $userId = $usersResponse.rows[0].doc._id
                Write-Host "Testing password change for user: $userId" -ForegroundColor Yellow
                
                $passwordChangeBody = @{
                    password = "newpassword123"
                } | ConvertTo-Json
                
                $passwordResponse = Invoke-RestMethod -Uri "http://localhost:8000/api/v1/users/$userId" `
                    -Method PUT `
                    -Headers $headers `
                    -Body $passwordChangeBody
                
                Write-Host "Password change successful!" -ForegroundColor Green
                $passwordResponse | ConvertTo-Json -Depth 2
            } else {
                Write-Host "No users found to test password change" -ForegroundColor Yellow
            }
        } catch {
            Write-Host "Password change error: $($_.Exception.Message)" -ForegroundColor Red
            if ($_.Exception.Response) {
                Write-Host "Status: $($_.Exception.Response.StatusCode)" -ForegroundColor Red
            }
        }
        
    } else {
        Write-Host "No token received!" -ForegroundColor Red
    }
    
} catch {
    Write-Host "Login error: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "Response: $($_.Exception.Response)" -ForegroundColor Red
}

Write-Host "`n=== Test Complete ===" -ForegroundColor Green