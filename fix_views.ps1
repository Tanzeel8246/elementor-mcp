$files = @(
    "includes/admin/views/page-tools.php",
    "includes/admin/views/page-connection.php",
    "assets/js/admin.js"
)
foreach ($f in $files) {
    if (Test-Path $f) {
        $content = [System.IO.File]::ReadAllText($f, [System.Text.Encoding]::UTF8)
        $content = $content.Replace("elementor-mcp", "mindcrafts-ai")
        $content = $content.Replace("elementorMcp", "mindcraftsAi")
        $content = $content.Replace("Elementor MCP", "MindCrafts AI")
        $content = $content.Replace("Elementor_MCP_", "MindCrafts_AI_")
        [System.IO.File]::WriteAllText($f, $content, [System.Text.Encoding]::UTF8)
        Write-Host ("Updated " + $f)
    }
}
