<script>
    $( "#addpdcmodal" ).dialog( {
        autoOpen: {if $action}true{else}false{/if},
        resizable: false,
        width: 'auto',
        modal: true,
        title: "{if $action == 'modify'}{trans("Modify purchase category")} {$pdcinfo.id}{else}{trans("Add purchase category")}{/if}"
    });

    $( "#close" ).click(function() {
        $( "#addpdcmodal" ).dialog( "close" );
    });

    function open_add_dialog() {
        $( "#addpdc-form" ).attr('action', '?m=pdcategorylist&action=add');
        $( "#submit-modal-button" ).html('<i class="lms-ui-icon-submit"></i><span class="lms-ui-label">{trans("Add")}</span>');

        $( "#dialog-id", "#dialog-name", "#dialog-description", "#dialog-userids" ).val();
        $( "#addpdcmodal" ).dialog( "option", "title", "{trans("Add purchase document type")}").dialog( "open" );
    };

    function open_modify_dialog (template_id) {
        $( "#submit-modal-button" ).html('<i class="lms-ui-icon-submit"></i><span class="lms-ui-label">{trans("Submit")}</span>');
        $( "#addpdc-form" ).attr('action', '?m=pdcategorylist&action=modify&id=' + template_id);

    if (template_id) {
        let pc = get_ajax_pdcategoryinfo(template_id);
        $("#dialog-name").val(pc.name);
        $("#dialog-description").val(pc.description);
        if (pc.userids) {
            let userids = Object.keys(pc.userids);
            $( "#dialog-userids").val(userids);
        }
    }
    updateAdvancedSelects( "select[id^='dialog-']" );
    $( "#addpdcmodal" ).dialog( "option", "title", "{trans("Modify purchase document type")} " + template_id).dialog( "open" );
};

    function get_ajax_pdcategoryinfo(id){
        var response = $.get({
        url: '?m=pdcategorylist',
        type: 'GET',
        dataType: "json",
        async: false,
        data: "catid=" + id,
        });
        return response.responseJSON;
    };

    $(function() {
        $( '.delete-pdc' ).click(function() {
            confirmDialog( $t("Are you sure you want to delete that purchase category?") , this).done(
                function() { location.href = $(this).attr('href'); });
                return false;
        });
    });
</script>
