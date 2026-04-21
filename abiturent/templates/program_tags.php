<?php
// templates/program_tags.php
function renderProgramTags($program) {
    ob_start();
    ?>
    <div class="tags">
        <?php if ($program['is_professionalitet_related']): ?>
            <span class="attribute-tag professionalitet-tag">Профессионалитет</span>
        <?php endif; ?>
        
        <?php if (!empty($program['attributes_array'])): ?>
            <?php foreach ($program['attributes_array'] as $attr):
                $attr_lower = mb_strtolower(trim($attr));
                $tag_class = 'attribute-tag';
                
                if (strpos($attr_lower, '2 огэ') !== false) {
                    continue;
                }
                if (strpos($attr_lower, "профессия") !== false) {
                    $tag_class .= ' profession';
                }
            ?>
                <span class="<?php echo $tag_class; ?>"><?php echo htmlspecialchars($attr); ?></span>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
?>