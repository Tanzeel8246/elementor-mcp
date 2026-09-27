# MindCrafts AI — Rebranding Script
# چلانے کا طریقہ: powershell -File rebrand.ps1

$root = $PSScriptRoot
$files = Get-ChildItem -Path $root -Recurse -Include "*.php" | Where-Object { $_.Name -ne "mindcrafts-ai.php" }

$replacements = @(
    @{ From = "Elementor_MCP_Plugin";              To = "MindCrafts_AI_Plugin" },
    @{ From = "class Elementor_MCP_Data";          To = "class MindCrafts_AI_Data" },
    @{ From = "new Elementor_MCP_Data";            To = "new MindCrafts_AI_Data" },
    @{ From = ": Elementor_MCP_Data";              To = ": MindCrafts_AI_Data" },
    @{ From = "Elementor_MCP_Element_Factory";     To = "MindCrafts_AI_Element_Factory" },
    @{ From = "Elementor_MCP_Schema_Generator";    To = "MindCrafts_AI_Schema_Generator" },
    @{ From = "Elementor_MCP_Control_Mapper";      To = "MindCrafts_AI_Control_Mapper" },
    @{ From = "Elementor_MCP_Settings_Validator";  To = "MindCrafts_AI_Settings_Validator" },
    @{ From = "Elementor_MCP_Element_Validator";   To = "MindCrafts_AI_Element_Validator" },
    @{ From = "Elementor_MCP_Ability_Registrar";   To = "MindCrafts_AI_Ability_Registrar" },
    @{ From = "Elementor_MCP_Query_Abilities";     To = "MindCrafts_AI_Query_Abilities" },
    @{ From = "Elementor_MCP_Page_Abilities";      To = "MindCrafts_AI_Page_Abilities" },
    @{ From = "Elementor_MCP_Layout_Abilities";    To = "MindCrafts_AI_Layout_Abilities" },
    @{ From = "Elementor_MCP_Widget_Abilities";    To = "MindCrafts_AI_Widget_Abilities" },
    @{ From = "Elementor_MCP_Template_Abilities";  To = "MindCrafts_AI_Template_Abilities" },
    @{ From = "Elementor_MCP_Global_Abilities";    To = "MindCrafts_AI_Global_Abilities" },
    @{ From = "Elementor_MCP_Composite_Abilities"; To = "MindCrafts_AI_Composite_Abilities" },
    @{ From = "Elementor_MCP_Stock_Image_Abilities"; To = "MindCrafts_AI_Stock_Image_Abilities" },
    @{ From = "Elementor_MCP_Openverse_Client";    To = "MindCrafts_AI_Openverse_Client" },
    @{ From = "Elementor_MCP_Id_Generator";        To = "MindCrafts_AI_Id_Generator" },
    @{ From = "Elementor_MCP_Admin";               To = "MindCrafts_AI_Admin" },
    @{ From = "ELEMENTOR_MCP_VERSION";             To = "MINDCRAFTS_AI_VERSION" },
    @{ From = "ELEMENTOR_MCP_DIR";                 To = "MINDCRAFTS_AI_DIR" },
    @{ From = "ELEMENTOR_MCP_URL";                 To = "MINDCRAFTS_AI_URL" },
    @{ From = "ELEMENTOR_MCP_BASENAME";            To = "MINDCRAFTS_AI_BASENAME" },
    @{ From = "'elementor-mcp'";                   To = "'mindcrafts-ai'" },
    @{ From = '"elementor-mcp"';                   To = '"mindcrafts-ai"' },
    @{ From = "elementor_mcp_disabled_tools";      To = "mindcrafts_ai_disabled_tools" },
    @{ From = "elementor_mcp_settings";            To = "mindcrafts_ai_settings" },
    @{ From = "elementor_mcp_ability_names";       To = "mindcrafts_ai_ability_names" },
    @{ From = "elementor-mcp-admin";               To = "mindcrafts-ai-admin" },
    @{ From = "elementor-mcp-server";              To = "mindcrafts-ai-server" },
    @{ From = "elementorMcpAdmin";                 To = "mindcraftsAiAdmin" },
    @{ From = "@package Elementor_MCP";            To = "@package MindCrafts_AI" },
    @{ From = "Elementor MCP";                     To = "MindCrafts AI" }
)

$totalUpdated = 0

foreach ($file in $files) {
    $content = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::UTF8)
    $original = $content

    foreach ($r in $replacements) {
        $content = $content.Replace($r.From, $r.To)
    }

    if ($content -ne $original) {
        [System.IO.File]::WriteAllText($file.FullName, $content, [System.Text.Encoding]::UTF8)
        Write-Host "Updated: $($file.Name)"
        $totalUpdated++
    }
}

Write-Host ""
Write-Host "Rebranding complete! $totalUpdated files updated."
