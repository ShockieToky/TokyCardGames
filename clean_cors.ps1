# PowerShell script to clean CORS headers from all controllers

$controllers = Get-ChildItem "c:\Users\AYMERICK\Documents\GitHub\TokyCardGames\server\src\Controller\*.php"

foreach ($controller in $controllers) {
    Write-Host "Cleaning $($controller.Name)"
    
    $content = Get-Content $controller.FullName -Raw
    
    # Remove CORS headers from JsonResponse calls - simple approach
    $content = $content -replace ", \[\s*\r?\n\s*'Access-Control-[^\]]*\]", ""
    $content = $content -replace ", \[\s*\r?\n\s*`"`"Access-Control-[^\]]*\]", ""
    
    # Remove OPTIONS from route methods  
    $content = $content -replace "methods: \['([^']*)', 'OPTIONS'\]", "methods: ['`$1']"
    $content = $content -replace "methods: \['OPTIONS', '([^']*)'\]", "methods: ['`$1']"
    $content = $content -replace "methods: \['OPTIONS'\]", "methods: ['GET']"
    
    Set-Content -Path $controller.FullName -Value $content -NoNewline
}

Write-Host "CORS cleanup completed!"