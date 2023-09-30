<?php
/** Form for adding a new Custom Status term **/ 
?>

<form class='add:the-list:' action="<?php esc_url(\PublishPress_Statuses::getLink()); ?>"
method='post' id='addstatus' name='addstatus'>

    <?php
    wp_nonce_field('custom-status-add-nonce');

    require_once(__DIR__ . '/StatusEditUI.php');
    \PublishPress_Statuses\StatusEditUI::mainTabContent();
    ?>
    <input type="hidden" name="page" value="publishpress-statuses" />
    <input type="hidden" name="action" value="add-status" />

    <?php if (\PublishPress_Statuses::TAXONOMY_PRIVACY === \PublishPress_Functions::REQUEST_key('taxonomy')) :?>
    <input type="hidden" name="taxonomy" value="<?php echo \PublishPress_Statuses::TAXONOMY_PRIVACY;?>" />
    <?php endif;?>

    <p class='submit'><?php
        submit_button(
            __('Add New Status', 'publishpress-statuses'),
            'primary',
            'submit',
            false
        ); ?>&nbsp;</p>
</form>