/* Shared DataTables defaults for invoices and administration. */
(() => {
    'use strict';
    const source = document.currentScript;
    $.extend(true, $.fn.dataTable.defaults, {
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        autoWidth: false,
        language: { url: source.dataset.language },
        dom: '<"row align-items-center"<"col-sm-6"l><"col-sm-6"f>>rt<"row align-items-center"<"col-sm-5"i><"col-sm-7"p>>'
    });
    $(function () {
        document.querySelectorAll('table[data-datatable]').forEach(table => {
            if ($.fn.dataTable.isDataTable(table)) return;
            const actions = [...table.tHead.rows[0].cells]
                .map((cell, index) => cell.hasAttribute('data-table-actions') ? index : -1)
                .filter(index => index >= 0);
            $(table).DataTable({
                order: [],
                columnDefs: actions.length ? [{ targets: actions, orderable: false, searchable: false }] : []
            });
        });
    });
})();
