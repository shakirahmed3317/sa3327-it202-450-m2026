<?php
function courseLabel(string $code, string $title): string {
    return $code . " - " . $title;
}

echo courseLabel("IT202", "Internet Applications");
?>