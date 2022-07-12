<script>
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
</script>
