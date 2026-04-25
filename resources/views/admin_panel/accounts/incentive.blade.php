<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Expense Entry Enhanced</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        td, th {
            padding: 0 !important;
            vertical-align: middle !important;
        }
        td input, td select {
            border: none;
            width: 100%;
            padding: 4px 6px;
            box-shadow: none;
            background-color: transparent;
        }
    </style>
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Income Voucher</h5>
        </div>
        <div class="card-body">
            <form id="expense-form">
                <div class="row mb-3">
                     <div class="col-md-2">
                        <label class="form-label">Sales Officer</label>
                        <input type="text" class="form-control text-muted" readonly value="{{ Auth::user()->name }}">
                    </div>
                    <div class="col-md-2">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" name="date">
                    </div>
                    <div class="col-md-3">
                        <label for="type" class="form-label">Select Type</label>
                        <select class="form-select" id="type" name="type">
                            <option value="">-- Choose --</option>
                            <option value="customer">Customer</option>
                            <option value="sub-customer">Sub Customer</option>
                            <option value="supplier">Supplier</option>
                        </select>
                    </div>
                    <div class="col-md-5 ">
                        <label for="person" class="form-label">Party</label>
                        <select class="form-select" id="person" name="person">
                            <option value="">-- Select --</option>
                            <option>Waleed</option>
                            <option>Walking</option>
                            <option>Vendor A</option>
                            <option>Vendor B</option>
                        </select>
                    </div>
                   
                    
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered text-center" id="expense-table">
                        <thead class="table-secondary">
                            <tr>
                                <th>Sub-Heads</th>
                                <th>Narration</th>
                                <th>Amount (-/+)</th>
                            </tr>
                        </thead>
                        <tbody id="expense-body">
                            <tr>
                                
                                <td>
                                    <select class="form-select" name="sub_head[]">
                                        <option value="">Fuel</option> 
                                        <option value="">Transport</option> 
                                        <option value="">Miscellaneous</option> 
                                    </select>
                                </td>
                                <td>
                                    <!-- Editable Narration input -->
                                    <input type="text" class="form-control" name="narration[]" placeholder="Narration (e.g. fuel for delivery)">
                                </td>
                                <td>
                                    <input type="number" class="form-control" name="amount[]" placeholder="0.00">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <button type="submit" class="btn btn-outline-success">Save</button>
                <button type="submit" class="btn btn-success">Save & Post</button>
            </form>
        </div>
    </div>
</div>

<!-- Auto Row Add Script -->
<script>
    document.addEventListener('keydown', function (e) {
        const active = document.activeElement;
        const isAmount = active && active.tagName === 'INPUT' && active.name === 'amount[]';

        if ((e.key === 'Tab' || e.key === 'Enter') && isAmount) {
            const row = active.closest('tr');
            const newRow = row.cloneNode(true);

            // Clear all values
            newRow.querySelectorAll('input, select').forEach(el => el.value = '');

            e.preventDefault();
            row.parentNode.appendChild(newRow);

            newRow.querySelector('select').focus();
        }
    });
</script>

</body>
</html>
