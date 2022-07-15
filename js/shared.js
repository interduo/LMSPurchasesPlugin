<script>
    function convert_expence_values(elem_netvalue, elem_taxid, elem_grossvalue) {
        let elemid = event.target.id;
        let expenceid = elemid.replace(/\D/g, "");
        console.log("kliknięte pole: " + elemid + ' w wierszu: ' + expenceid);

        let elemtax = document.getElementById("#dialog-taxid" + expenceid);
        console.log(elemtax);

        switch(elemid) {
            case 'dialog-grosscurrencyvalue' + expenceid:
                console.log("wyliczam netto uzywając taxid");
                break;
            case 'dialog-taxid' + expenceid:
            case 'dialog-netcurrencyvalue' + expenceid:
            case 'dialog-amount' + expenceid:
            default:
                console.log("wyliczam brutto uzywając taxid");
                break;
        }
    }

    function makeMultiselectOptionsSelectedUsingValues(elem, values) {
        $( "#" + elem).val(values);
    }

    function getColumnFromArray(matrix, col) {
        var column = [];
        for (var i=0; i<matrix.length; i++) {
            column.push(matrix[i][col]);
        }
        return column;
    }

    function clear_pd_form(formid) {
        $("#" + formid)[0].reset();
        $("#column2").html('').addClass('hidden').removeClass('pdf-loaded');
        $("input[form='" + formid + "']").val('');
        $("select[form='" + formid + "'] option:selected").removeAttr('selected');
        $( "#filecontainer").removeClass('hidden');

        $("#dialog-iban").show();
        $("#bankaccounts-container").empty();

        $('#dialog-typeid option[value="{$default_document_typeid}"]').attr("selected", "selected");

        $("#dialog-divisionid option[value='" + {$default_divisionid} + "']").attr("selected", "true");
        $("#dialog-divisionid").val( {$default_divisionid} );

        document.querySelectorAll('div.fileupload-files, div#herewillbethepdf').forEach(e => e.innerHTML = '');

        $("#dialog-currency option[value='" + '{$default_currency}' + "']").attr("selected", "true");
        $("#dialog-paytype option[value='" + '{$default_paytype}' + "']").attr("selected", "true");

        $('.lms-ui-customer-select-name').html('<a href=""></a>');
        $('#dialog-supplierid').trigger('input');
        $(".cloned").remove();

        updateAdvancedSelects( "select[id^='dialog-']" );

        $("#submit-modal-button").html('<i class="lms-ui-icon-submit"></i><span class="lms-ui-label">{trans("Add")}</span>');
    }
</script>
