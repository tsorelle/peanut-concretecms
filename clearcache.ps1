# Run from project root
# https://documentation.concrete5.org/developers/appendix/cli-commands

Clear-Host
Set-Location -Path "web.root"
& "concrete/bin/concrete" c5:clear-cache
Set-Location -Path ".."