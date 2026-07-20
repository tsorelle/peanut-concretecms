$ThemeRoot = "packages\theme_peanut\themes\peanut"
$CssDir = "css"
$JsDir = "js"

$SrcCss = Join-Path $PSScriptRoot "output\css"
$SrcJs = Join-Path $PSScriptRoot "output\js"

$DestCss = Join-Path $PSScriptRoot "..\web.root\$ThemeRoot\$CssDir"
$DestJs = Join-Path $PSScriptRoot "..\web.root\$ThemeRoot\$JsDir"

# Ensure destination directories exist
if (!(Test-Path $DestCss)) { New-Item -ItemType Directory -Path $DestCss -Force }
if (!(Test-Path $DestJs)) { New-Item -ItemType Directory -Path $DestJs -Force }

# Copy CSS files
if (Test-Path $SrcCss) {
    Copy-Item -Path "$SrcCss\*.css" -Destination $DestCss -Force
}

# Copy JS files
if (Test-Path $SrcJs) {
    Copy-Item -Path "$SrcJs\*.js" -Destination $DestJs -Force
}
