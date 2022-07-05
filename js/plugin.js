<script>
    $( "#addpdmodal" ).dialog( {
        autoOpen: {if isset($action)}true{else}false{/if},
        resizable: false,
        width: 'auto',
        height: 'auto',
        modal: true,
        title: "{if !isset($action) || $action == 'add'}{trans("Add purchase document")}{else}{trans("Modify purchase document")} {$pdinfo.id}{/if}"
    });

    $( "#close" ).click(function() {
        $( "#addpdmodal" ).dialog( "close" );
    });

    function clear_pd_form() {
        $("#addpd-form")[0].reset();
        $("#column2").html('').addClass('hidden').removeClass('pdf-loaded');
        $("input").val('');
        $('option:selected').removeAttr('selected');
        $( "#filecontainer").removeClass('hidden');

        $("#dialog-iban").show();
        $("#bankaccounts-container").empty();

        $('#dialog-typeid option[value="{$default_document_typeid}"]').attr("selected", "selected");

        $("#dialog-divisionid option[value='" + {$default_divisionid} + "']").attr("selected", "true");
        $("#dialog-divisionid").val( {$default_divisionid} );

        document.querySelectorAll('div.fileupload-files, div#herewillbethepdf').forEach(e => e.innerHTML = '');

        $("#dialog-currency option[value='" + '{$default_currency}' + "']").attr("selected", "true");
        change_currency();

        $("#dialog-paytype option[value='" + '{$default_paytype}' + "']").attr("selected", "true");
        change_pay_type();

        $('.lms-ui-customer-select-name').html('<a href=""></a>');
        $('#dialog-supplierid').trigger('input');

        //clear expences - start
        $(".cloned").remove();
        $('#dialog-amount0').val('1');

        var selectelem = document.querySelector('select#dialog-taxid0');
        selectelem.value = selectelem.getAttribute('data-default-value');
        //clear expences - end

        updateAdvancedSelects( "select[id^='dialog-']" );
        document.getElementById("addpd-form").setAttribute('action', '?m=pdlist&action=add');
        $("#submit-modal-button").html('<i class="lms-ui-icon-submit"></i><span class="lms-ui-label">{trans("Add")}</span>');
    }

    function open_add_dialog() {
        clear_pd_form();

        $( "#addpdmodal" ).dialog({
          width: 'auto',
          height: 'auto',
          title: "{trans('Add purchase document')}"
        }).dialog( "open" );
    };

    function open_add_anteroom_dialog(attid) {
        clear_pd_form();

        $( "#filecontainer").addClass('hidden');
        show_inline_pdf_from_link('?m=pdview&attid=' + attid);

        $( "#addpdmodal" ).dialog({
          width: 'auto',
          height: $(window).height()*0.95,
          resizable: true,
          title: "{trans('Add purchase document from anteroom')}"
        }).dialog( "open" );
    };

    function get_ajax_pdinfo(id){
        var response = $.get({
            url: '?m=pdlist',
            type: 'GET',
            dataType: "json",
            async: false,
            data: "pdid=" + id,
        });
        return response.responseJSON;
    };

    function documentExist(supplierid, fullnumber){
        var response = $.post({
            url: '?m=pdlist&documentexist',
            type: 'POST',
            dataType: "json",
            async: false,
            data: { supplierid: supplierid, fullnumber: fullnumber },
        });
        return response.responseJSON;
    };

    function open_modify_dialog (template_id) {
        clear_pd_form();
        $( "#submit-modal-button" ).html('<i class="lms-ui-icon-submit"></i><span class="lms-ui-label">{trans("Submit")}</span>');
        $( "#addpd-form" ).attr('action', '?m=pdlist&action=modify&id=' + template_id);
        $( "#addpd-form" ).attr('data-templateid-number', template_id);

        if (template_id) {
            var pd = get_ajax_pdinfo(template_id);
            if (pd.typeid) {
                $("#dialog-typeid").val(pd.typeid).attr('data-template-typeid');
            }

            $("#dialog-deadline").val(pd.deadline_formatted);
            $("#dialog-divisionid").val(pd.divisionid);
            $("#dialog-divisionid option[value='" + pd.paytype + "']").attr("selected", "true");

            $("#dialog-currency").val(pd.currency);
            $("#dialog-vatplnvalue").val(pd.vatplnvalue);
            change_currency();

            $("#dialog-fullnumber").val(pd.fullnumber);
            $("#dialog-sdate").val(pd.sdate_formatted);
            $("#dialog-paydate").val(pd.paydate_formatted);

            $("#dialog-paytype").val(pd.paytype);
            $("#dialog-paytype option[value='" + pd.paytype + "']").attr("selected", "true");
            change_pay_type();

            $("#dialog-iban").val(pd.iban);

            $("#dialog-supplierid").val(pd.supplierid).trigger('input');

            $( ".cloned" ).remove();

            for (let index = 0; index < pd.expences_count; index++) {
                if (index != 0) {
                    add_expence_row('expencestable');
                };

                $("#dialog-netcurrencyvalue" + index).val(pd.expences[index].netcurrencyvalue);
                $("#dialog-amount" + index).val(pd.expences[index].amount);
                $("#dialog-taxid" + index).val(pd.expences[index].taxid);
                $("#dialog-taxid" + index + " option[value='" + pd.expences[index].taxid + "']").attr("selected", "true");
                $("#dialog-description" + index).val(pd.expences[index].description);

                if (pd.expences[index].invprojects) {
                    makeMultiselectOptionsSelectedUsingValues('dialog-invprojects' + index, getColumnFromArray(pd.expences[index].invprojects, "invprojectid"));
                }

                if (pd.expences[index].categories) {
                    makeMultiselectOptionsSelectedUsingValues('dialog-categories' + index, getColumnFromArray(pd.expences[index].categories, "categoryid"));
                }
            }
            updateAdvancedSelects( "select[id^='dialog-']" );
        }

        $( "#addpdmodal" ).dialog({
            width: 'auto',
            height: 'auto',
            title: "{trans("Modify purchase document")} " + template_id
        }).dialog( "open" )

        if (pd.fileupload !== null) {
            show_inline_pdf_from_link('?m=pdview&id=' + template_id);
        }
    };

    function getColumnFromArray(matrix, col) {
        var column = [];
        for(var i=0; i<matrix.length; i++) {
            column.push(matrix[i][col]);
        }
        return column;
    }

    function makeMultiselectOptionsSelectedUsingValues(elem, values) {
        $( "#" + elem).val(values);
    }

    function increaseStringValue(str){
      if (!str) {
          return;
      }
      return str.replaceAll(/\d+/ig,
        function(a){ return +a+1;});
    }

    function reindexIdNameInAllElementsOfTable(tableid) {
        var table = document.getElementById(tableid);
        for (var i = 0, row; row = table.rows[i]; i++) {
            if (i == '0') {
                $( row ).removeClass('cloned');
            }
            setIndexIdNameInAllElementsOfTableRow(row, i);
            row.setAttribute("id", row.getAttribute('id').replaceAll(/\d+/ig, i));
        }
    }

    function setIndexIdNameInAllElementsOfTableRow(elem, indexnum) {
        var elements = elem.querySelectorAll("tr, div, td, select, input:not(.chosen-search-input), button");
        for (let i = 0; i < elements.length; i++) {
          var e = elements[i];
          if (e.getAttribute('id')) {
            e.setAttribute("id", e.getAttribute('id').replaceAll(/\d+/ig, indexnum));
          }
          if (e.getAttribute('name')) {
            e.setAttribute("name", e.getAttribute('name').replaceAll(/\d+/ig, indexnum));
          }
        }
    }

    function increaseIdNameInAllElementsOfTableRow(elem) {
        var elementList = elem.querySelectorAll("select, input, tr, button");
        for (let i = 0; i < elementList.length; i++) {
          elementList[i].setAttribute("id", increaseStringValue(elementList[i].getAttribute('id')));
          elementList[i].setAttribute("name", increaseStringValue(elementList[i].getAttribute('name')));
        }
        elem.setAttribute("id", increaseStringValue(elem.getAttribute('id')));
    }

    function add_expence_row(tableid) {
        var row = document.getElementById(tableid).lastElementChild.lastElementChild.cloneNode(true);
        row.classList.add("cloned");
        row.querySelectorAll('input, textarea, select').forEach(e => e.value = '');
        row.querySelectorAll('div.lms-ui-advanced-select').forEach(e => e.remove());
        row.querySelectorAll('select.lms-ui-advanced-select').forEach(function(e) { e.style = null; initAdvancedSelects(e); });

        row.querySelectorAll("select[id^='dialog-taxid'] option").forEach(e => e.removeAttribute("selected"));
        var taxid_select = row.querySelector("select[id^='dialog-taxid']");
        var defaulttaxid = taxid_select.getAttribute('data-default-value');

        if (typeof(defaulttaxid) == 'undefined') {
            taxid_select.selectedIndex=0;
        } else {
            taxid_select.value = defaulttaxid;
        }

        row.querySelector("input[id^='dialog-amount']").value = 1;

        document.getElementById(tableid).lastElementChild.lastElementChild.after(row);
        reindexIdNameInAllElementsOfTable(tableid);
        delete row;

        $('#addpdmodal').dialog('option', 'height', 'auto')
    }

    function clone_expence_row(elem) {
        var kopia = document.getElementById(elem).parentElement.parentElement.parentElement.cloneNode(true);
        kopia.classList.add("cloned");
        kopia.querySelectorAll('div.lms-ui-advanced-select').forEach((e) => e.remove());
        kopia.querySelectorAll('select.lms-ui-advanced-select').forEach(
            function(e) { e.style = null; initAdvancedSelects(e); }
        );

        document.getElementById(elem).parentElement.parentElement.parentElement.after(kopia);

        reindexIdNameInAllElementsOfTable('expencestable');
    };

    function delete_nearest_tr(btnid) {
        var elem = document.getElementById(btnid);
        var tableid = elem.closest('table').id;
        var rowid = elem.closest('tr').id;
        delete_table_row(tableid, rowid);
    }

    function delete_table_row (tableid, rowid) {
        var rowscount = document.getElementById(tableid).rows.length;

        if (rowscount > 1) {
            document.getElementById(rowid).remove();
        } else {
            alert("{trans('Could not remove only row')}");
        }
        reindexIdNameInAllElementsOfTable(tableid);
    }

    function change_currency() {
        var elemtr = document.getElementById('dialog-vatinpln-tr');
        var elem = document.getElementById('dialog-currency');
        var vatplnvalue = document.getElementById('dialog-vatplnvalue');

        if (elem.value === 'PLN') {
            vatplnvalue.value = '';
            vatplnvalue.disabled=true;
            vatplnvalue.removeAttribute('required');
            elemtr.classList.add('lms-ui-disabled');
        } else {
            vatplnvalue.disabled=false;
            vatplnvalue.setAttribute('required', '');
            elemtr.classList.remove('lms-ui-disabled');
        }
    }

    function show_hide_customeraddbtn() {
        let btn = document.getElementById('customeraddbtn');
        let supplierid = document.getElementById('dialog-supplierid').value;

        if (supplierid == '') {
            btn.classList.remove('hidden');
        } else {
            btn.classList.add('hidden');
        }
    }

    function change_pay_type() {
        var elem = document.getElementById('dialog-paytype');
        var paytype = elem.value;
        var iban = document.getElementById("dialog-iban-tr");
        var ibaninput = document.getElementById("dialog-iban");
        var iban_button = document.getElementById("import_iban_button");

        if (paytype != 2 && paytype != 3) {
            iban.removeAttribute('required');
            iban.classList.add("lms-ui-disabled");
            iban_button.disabled = true;
            ibaninput.removeAttribute('required');
            ibaninput.disabled = true;
            if (paytype == 1 || paytype == 8) {
                let paydate = document.getElementById("dialog-paydate");
                let deadline = document.getElementById("dialog-deadline");
                let sdate = document.getElementById("dialog-sdate").value;
                paydate.value = sdate;
                deadline.value = sdate;
            }
        } else {
            iban.classList.remove("lms-ui-disabled");
            ibaninput.setAttribute('required','');
            ibaninput.disabled = false;
            iban_button.disabled = false;
        }
    }

    function selectOnlyThis(id) {
        var clickedcheckbox = document.getElementsByClassName("onlyoneselectedcheckbox");
        Array.prototype.forEach.call(clickedcheckbox, function(el){ el.checked = false; });
        id.checked = true;
    }

    function get_customer_ten(customerid) {
        var response = $.get({
            url: '?m=pdlist',
            type: 'GET',
            dataType: "json",
            async: false,
            data: "get_customer_ten=" + customerid,
        });
        return response.responseJSON;
    }

    function format_number_to_iban(str, index, arr) {
        arr[index] = str.substr(0, 2) + " " + str.substr(2, 4) + " " + str.substr(6, 4) + " " + str.substr(10, 4) + " " + str.substr(14, 4) + " " + str.substr(18, 4) + " " + str.substr(22, 4)
    };


    function fill_iban() {
        var customerid = document.getElementById("dialog-supplierid").value;
        var customerten = get_customer_ten(customerid);

        if (customerten) {
            var ibans = import_iban(customerten);
            ibans.forEach(format_number_to_iban);

            var ibansHTML = '';
            for (let i=0; i < ibans.length; i++) {
                ibansHTML += '<input id="iban' + i + '" type="checkbox" name="addpd[iban]" value="' + ibans[i] + '" class="onlyoneselectedcheckbox" onchange="selectOnlyThis(iban' + i + ')">' + ibans[i];
                if (ibans[i] != ibans.length) {
                    ibansHTML += '<br>';
                }
            }
            var ibaninput = document.getElementById("dialog-iban");
            ibaninput.style.display='none';
            ibaninput.removeAttribute('required');
            document.getElementById('bankaccounts-container').innerHTML = ibansHTML;
        } else {
            alert( '{trans("No supplier choosen or supplier got no TEN")}' );
        }
    }

    function import_iban(nip) {
        var dateObj = new Date();
        var month = ('0' + (dateObj.getMonth()+1)).slice(-2);
        var day = ('0' + dateObj.getDate()).slice(-2);
        var year = dateObj.getFullYear();
        var currentdate = year + "-" + month + "-" + day;

        var iban = $.get({
            url: 'https://wl-api.mf.gov.pl/api/search/nip/' + nip,
            type: 'GET',
            async: false,
            data: 'date=' + currentdate,
        });
        return iban.responseJSON.result.subject.accountNumbers;
    }

    function show_inline_pdf_from_link(pdflink) {
        if (!pdflink) {
            alert("No or bad pdf link attached");
            return;
        }

        $('#column2').addClass('pdf-loaded').html('<object data="' + pdflink + '" type="application/pdf" height="100%" width="100%"></object>').removeClass('hidden');
        $( "#addpdmodal" ).dialog( { width: window.innerWidth*0.9, height: window.innerHeight*0.9 } );
    }

    // function convert_expence_values() {
    //     //TODO: dokoncze przeliczanie jak bede robił statystyki ;-)
    //
    //     return;
    //     var elemid = event.target.id;
    //     var expenceid = elemid.replace(/\D/g, "");
    //
    //     var elemnetto = document.getElementById('dialog-netcurrencyvalue' + expenceid);
    //     var elemgross = document.getElementById('dialog-grosscurrencyvalue' + expenceid);
    //     console.log("kliknięte pole: " + elemid + ' w wierszu: ' + expenceid);
    //
    //     var elemtax = document.getElementById("#dialog-taxid" + expenceid);
    //     console.log(elemtax);
    //     var selopt = elemtax.forEach(a => document.querySelectorAll('option[selected]'));
    //     console.log(selopt);
    //
    //
    //     switch(elemid) {
    //         case 'dialog-grosscurrencyvalue' + expenceid:
    //             console.log("wyliczam netto uzywając taxid");
    //             break;
    //         case 'dialog-taxid' + expenceid:
    //         case 'dialog-netcurrencyvalue' + expenceid:
    //         case 'dialog-amount' + expenceid:
    //         default:
    //             console.log("wyliczam brutto uzywając taxid");
    //             ///elemgross.value = elemnetto.value * elemtax
    //             break;
    //     }
    // }

    // $( '#files').on('lms:fileupload:complete',
    //     function() {
    //         console.log('pokazuje podglada pliku X');
    //     }, false);

    document.getElementById('dialog-paytype').addEventListener("click", e => change_pay_type(), false);
    document.getElementById('dialog-currency').addEventListener("click", e => change_currency(), false);
    document.getElementById('dialog-fullnumber').addEventListener(
        "change", e => {
            let duplicateid = documentExist(document.getElementById('dialog-supplierid').value, document.getElementById('dialog-fullnumber').value);
            let duplicate_checker = document.getElementById('dialog-duplicate-checker');
            let template_id = document.getElementById('addpd-form').getAttribute('data-templateid-number');

            duplicate_checker.classList.remove('ui-icon-closethick', 'lms-ui-icon-check', 'hidden');
            duplicate_checker.removeAttribute('title');

            if (duplicateid && (typeof(template_id) == 'undefined'
                || (typeof(template_id) != 'undefined' && template_id != duplicateid))) {
                duplicate_checker.classList.add('lms-ui-icon-critical');
                alertDialog('{trans("There is the same full document number in database for this supplier - this is probably duplicate")}', this);
            } else {
                duplicate_checker.classList.add('lms-ui-icon-check');
            }
        }, false
    );

     $(function() {
         $( '.delete-pd' ).click(function() {
             confirmDialog( "{trans('Are you sure you want to delete that purchase document?')}" , this).done(function() {
                 location.href = $(this).attr('href');
             });
             return false;
         });
     });
</script>