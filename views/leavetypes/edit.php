<?php 

?>

<?php echo form_open('leavetypes/edit/' . $id); ?>
    <input type="hidden" name="id" value="<?php echo $id; ?>" />
    <label for="name"><?php echo lang('leavetypes_popup_update_field_name');?></label>
    <input type="text" name="name" value="<?php echo $type_name; ?>" />
    <br />
    <button id="send" class="btn btn-primary"><?php echo lang('leavetypes_popup_update_button_update');?></button>
</form>
