document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-table-search]').forEach(function (input) {
    const table = document.getElementById(input.dataset.tableSearch);
    if (!table) return;
    input.addEventListener('input', function () {
      const keyword = this.value.trim().toLowerCase();
      table.querySelectorAll('tbody tr').forEach(function (row) {
        row.style.display = row.innerText.toLowerCase().includes(keyword) ? '' : 'none';
      });
    });
  });

  document.querySelectorAll('[data-filter-target]').forEach(function (select) {
    const table = document.getElementById(select.dataset.filterTarget);
    if (!table) return;
    select.addEventListener('change', function () {
      const value = this.value.toLowerCase();
      table.querySelectorAll('tbody tr').forEach(function (row) {
        row.style.display = (!value || row.innerText.toLowerCase().includes(value)) ? '' : 'none';
      });
    });
  });
});
