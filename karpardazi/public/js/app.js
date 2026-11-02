/* سامانه کارپردازی — اسکریپت‌های سمت کاربر (کاملاً محلی) */
(function () {
    'use strict';

    function faToEn(s) {
        var fa = '۰۱۲۳۴۵۶۷۸۹', ar = '٠١٢٣٤٥٦٧٨٩';
        return String(s).replace(/[۰-۹٠-٩]/g, function (d) {
            var i = fa.indexOf(d);
            if (i > -1) return String(i);
            var j = ar.indexOf(d);
            return j > -1 ? String(j) : d;
        });
    }

    function digitsOnly(s) { return faToEn(s).replace(/[^0-9]/g, ''); }

    function groupDigits(s) {
        return s.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    // ورودی‌های پولی: جداکننده هزارگان هنگام تایپ
    function bindMoney(input) {
        var format = function () {
            var d = digitsOnly(input.value).replace(/^0+(?=\d)/, '');
            input.value = d ? groupDigits(d) : '';
        };
        input.addEventListener('input', format);
        format();
    }

    function numericValue(input) {
        var d = digitsOnly(input.value || '');
        return d ? parseInt(d, 10) : 0;
    }

    // تقویم شمسی
    if (window.jQuery && jQuery.fn.persianDatepicker) {
        jQuery('.jdate').persianDatepicker({
            format: 'YYYY/MM/DD',
            autoClose: true,
            observer: true,
            initialValue: false,
            calendar: { persian: { locale: 'fa' } }
        });
    }

    // جدول اقلام سند
    var tbody = document.getElementById('items-body');
    if (tbody) {
        var addBtn = document.getElementById('add-row');

        function moneyInputs() {
            tbody.querySelectorAll('input.money-input').forEach(function (inp) {
                if (!inp.dataset.bound) { bindMoney(inp); inp.dataset.bound = '1'; }
            });
        }

        function renumber() {
            var i = 0;
            tbody.querySelectorAll('tr').forEach(function (tr) {
                i++;
                tr.querySelectorAll('input, textarea').forEach(function (el) {
                    el.name = el.name.replace(/items\[\d+\]/, 'items[' + (i - 1) + ']');
                });
                var numCell = tr.querySelector('.row-num');
                if (numCell) numCell.textContent = i.toLocaleString('fa-IR');
            });
            recalc();
        }

        function recalc() {
            var a = 0, t = 0, b = 0;
            tbody.querySelectorAll('tr').forEach(function (tr) {
                a += numericValue(tr.querySelector('.item-amount') || { value: '' });
                t += numericValue(tr.querySelector('.item-tax') || { value: '' });
                b += numericValue(tr.querySelector('.item-insurance') || { value: '' });
            });
            var fmt = function (n) { return n ? n.toLocaleString('fa-IR') : '۰'; };
            var elA = document.getElementById('sum-amount'), elT = document.getElementById('sum-tax'),
                elB = document.getElementById('sum-insurance'), elG = document.getElementById('sum-grand');
            if (elA) elA.textContent = fmt(a);
            if (elT) elT.textContent = fmt(t);
            if (elB) elB.textContent = fmt(b);
            if (elG) elG.textContent = fmt(a + t + b);
        }

        function bindRow(tr) {
            tr.querySelectorAll('input.money-input').forEach(bindMoney);
            tr.querySelectorAll('input').forEach(function (inp) { inp.addEventListener('input', recalc); });
            var del = tr.querySelector('.del-row');
            if (del) {
                del.addEventListener('click', function () {
                    if (tbody.querySelectorAll('tr').length > 1) { tr.remove(); renumber(); }
                });
            }
            if (window.jQuery && jQuery.fn.persianDatepicker) {
                jQuery(tr).find('.jdate').persianDatepicker({
                    format: 'YYYY/MM/DD', autoClose: true, observer: true, initialValue: false
                });
            }
        }

        var rowTemplate = function (index) {
            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td class="row-num"></td>' +
                '<td><input type="text" name="items[' + index + '][expense_date]" class="form-control form-control-sm jdate" placeholder="1405/07/18"></td>' +
                '<td><input type="text" name="items[' + index + '][description]" class="form-control form-control-sm" maxlength="500"></td>' +
                '<td><input type="text" name="items[' + index + '][insurance]" class="form-control form-control-sm money-input item-insurance" inputmode="numeric"></td>' +
                '<td><input type="text" name="items[' + index + '][tax]" class="form-control form-control-sm money-input item-tax" inputmode="numeric"></td>' +
                '<td><input type="text" name="items[' + index + '][amount]" class="form-control form-control-sm money-input item-amount" inputmode="numeric"></td>' +
                '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger del-row" title="حذف ردیف">×</button></td>';
            return tr;
        };

        if (addBtn) {
            addBtn.addEventListener('click', function () {
                var tr = rowTemplate(tbody.querySelectorAll('tr').length);
                tbody.appendChild(tr);
                bindRow(tr);
                renumber();
            });
        }

        tbody.querySelectorAll('tr').forEach(bindRow);
        renumber();
    } else {
        document.querySelectorAll('input.money-input').forEach(bindMoney);
    }
})();
