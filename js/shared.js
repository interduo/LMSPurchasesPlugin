<script>

function convert_expence_values(changedel) {
    let expenceid = changedel.replace(/\D/g, "");
    let netid = document.getElementById("dialog-netcurrencyvalue" + expenceid);
    let amountid = document.getElementById("dialog-amount" + expenceid);
    let taxid = document.getElementById("dialog-taxid" + expenceid);
    let grossid = document.getElementById("dialog-grosscurrencyvalue" + expenceid);

    let netvalue = netid.value;
    let amountvalue = amountid.value;
    let netsumvalue = netvalue*amountvalue;
    let grossvalue = grossid.value;
    let taxrate = taxid.options[taxid.selectedIndex].getAttribute('data-taxrate-value');
    let taxvalue = taxrate/100;
    let vatsumvalue = netsumvalue*taxvalue/100;

    switch(changedel) {
        case 'dialog-grosscurrencyvalue' + expenceid:
            netid.value = Math.round(grossvalue/(1+taxvalue)/amountvalue);
            break;
        case 'dialog-taxid' + expenceid:
        case 'dialog-netcurrencyvalue' + expenceid:
        case 'dialog-amount' + expenceid:
        default:
            let sumnetvalue
            grossid.value = Math.round(netsumvalue*(1+taxvalue));
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
    $( "#files").removeClass('hidden');
    $( "#fileanteroom" ).addClass('hidden');

    $("#dialog-iban").show();
    $("#bankaccounts-container").empty();

    $('#dialog-typeid option[value="{$default_document_typeid}"]').attr("selected", "selected");

    $("#dialog-divisionid option[value='" + {$default_divisionid} + "']").attr("selected", "true");
    $("#dialog-divisionid").val( {$default_divisionid} );

    document.querySelectorAll('div.fileupload-files, div#column2').forEach(e => e.innerHTML = '');

    $("#dialog-currency option[value='" + '{$default_currency}' + "']").attr("selected", "true");
    $("#dialog-paytype option[value='" + '{$default_paytype}' + "']").attr("selected", "true");

    $('.lms-ui-customer-select-name').html('<a href=""></a>');
    $('#dialog-supplierid').trigger('input');
    $(".cloned").remove();

    updateAdvancedSelects( "select[id^='dialog-']" );

    $("#submit-modal-button").html('<i class="lms-ui-icon-submit"></i><span class="lms-ui-label">{trans("Add")}</span>');
}

</script>
