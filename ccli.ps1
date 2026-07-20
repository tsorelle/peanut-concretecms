# Run from project root
# https://documentation.concretecms.org/9-x/developers/security/cli-jobs

Clear-Host
Set-Location -Path "web.root"
& "concrete/bin/concrete" $args
Set-Location -Path ".."

