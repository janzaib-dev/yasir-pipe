import re

with open('resources/views/admin_panel/product/edit.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Replace the UOM selection
uom_html = """
                                <div class="col-md-2">
                                     <label class="form-label-pro">Base UOM <span class="text-danger">*</span></label>
                                     @php
                                         $basePkg = $product->packages->where('is_base', 1)->first();
                                         $baseSymbol = $basePkg ? $basePkg->symbol : 'pc';
                                     @endphp
                                     <select class="form-select form-control-pro form-select-pro" name="base_uom" id="base_uom" required>
                                         <option value="">Select...</option>
                                         <option value="m" {{ $baseSymbol == 'm' ? 'selected' : '' }}>m</option>
                                         <option value="cm" {{ $baseSymbol == 'cm' ? 'selected' : '' }}>cm</option>
                                         <option value="kg" {{ $baseSymbol == 'kg' ? 'selected' : '' }}>kg</option>
                                         <option value="gm" {{ $baseSymbol == 'gm' ? 'selected' : '' }}>gm</option>
                                         <option value="mt" {{ $baseSymbol == 'mt' ? 'selected' : '' }}>mt</option>
                                     </select>
                                 </div>
"""

content = re.sub(r'(<div class="col-md-3">\s*<label class="form-label-pro">Model / Series</label>)', uom_html + r'\1', content)
content = content.replace('col-md-8', 'col-md-7', 1)

# Remove section 2 and section 3 and replace with dynamic packaging section
packaging_section = """
            {{-- SECTION 2: PACKAGING & PRICING --}}
            <div class="section-card">
                <div class="card-header-pro">
                    <h5 class="card-title-pro"><i class="las la-boxes text-info"></i> Packaging Options</h5>
                    <button type="button" class="btn btn-sm btn-primary" id="addPackageRowBtn"><i class="las la-plus"></i> Add Row</button>
                </div>
                <div class="card-body-pro" style="overflow-x: auto;">
                    <table class="table table-bordered table-hover align-middle" id="packagingTable" style="min-width: 1200px;">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th style="width: 120px;">Code</th>
                                <th style="width: 150px;">Name</th>
                                <th style="width: 80px;">Symbol</th>
                                <th style="width: 80px;">Fraction</th>
                                <th>SKU</th>
                                <th style="width: 80px;">Weight</th>
                                <th style="width: 80px;">Height</th>
                                <th style="width: 80px;">Width</th>
                                <th style="width: 80px;">Length</th>
                                <th>Barcode</th>
                                <th style="width: 80px;">Conv Factor</th>
                                <th style="width: 100px;">Purch Price</th>
                                <th style="width: 100px;">Sale Price</th>
                                <th style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="packagingTbody">
                            <!-- Pre-filled via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
"""

start_sec_2 = content.find('{{-- SECTION 2: MEASUREMENTS & STOCK --}}')
end_sec_3 = content.find('{{-- Floating Save Button --}}')

if start_sec_2 != -1 and end_sec_3 != -1:
    content = content[:start_sec_2] + packaging_section + '\n            ' + content[end_sec_3:]

js_start = content.find('// --- Logic Update Mode ---')
js_end = content.find('// Image Handler')

new_js = """
            // --- Logic Update Mode ---
            const tbody = document.getElementById('packagingTbody');
            const addRowBtn = document.getElementById('addPackageRowBtn');
            const baseUomSelect = document.getElementById('base_uom');
            const productNameInput = document.querySelector('input[name="product_name"]');
            
            let rowCount = 0;
            const symbols = ['m', 'cm', 'inc', 'ft', 'pc', 'dz', 'kg', 'gm', 'lt', 'mt', 'ea'];
            let basePurchPrice = 0;
            let baseSalePrice = 0;

            const existingPackages = @json($product->packages);

            function generateRow(pkg = null, isBase = false) {
                rowCount++;
                const tr = document.createElement('tr');
                
                const codeSuffix = isBase ? '' : `-k${rowCount-1}`;
                const codeName = `packages[${rowCount}][code]`;
                const codeVal = pkg ? pkg.code : (isBase ? 'p-000001' : `p-000001${codeSuffix}`);
                
                const symVal = pkg ? pkg.symbol : '';
                const selectSymbol = symbols.map(s => `<option value="${s}" ${s === symVal ? 'selected' : ''}>${s}</option>`).join('');
                if(pkg && !symbols.includes(symVal)) {
                    selectSymbol += `<option value="${symVal}" selected>${symVal}</option>`;
                }
                
                tr.innerHTML = `
                    <td>
                        <input type="hidden" name="packages[${rowCount}][is_base]" value="${isBase ? 1 : 0}">
                        <input type="text" class="form-control form-control-sm row-code" name="${codeName}" value="${codeVal}" readonly>
                    </td>
                    <td><input type="text" class="form-control form-control-sm row-name" name="packages[${rowCount}][name]" value="${pkg ? (pkg.name||'') : ''}" ${isBase ? 'readonly' : ''}></td>
                    <td>
                        <select class="form-select form-select-sm row-symbol" name="packages[${rowCount}][symbol]" ${isBase ? 'readonly disabled' : ''} required>
                            <option value="">...</option>
                            ${selectSymbol}
                        </select>
                        ${isBase ? `<input type="hidden" class="hidden-base-symbol" name="packages[${rowCount}][symbol]" value="${symVal}">` : ''}
                    </td>
                    <td>
                        <select class="form-select form-select-sm row-fraction" name="packages[${rowCount}][is_fraction]" ${isBase ? 'readonly disabled' : ''}>
                            <option value="0" ${(pkg && !pkg.is_fraction) ? 'selected' : ''}>No</option>
                            <option value="1" ${(pkg && pkg.is_fraction) ? 'selected' : ''}>Yes</option>
                        </select>
                        ${isBase ? `<input type="hidden" name="packages[${rowCount}][is_fraction]" value="0">` : ''}
                    </td>
                    <td><input type="text" class="form-control form-control-sm" name="packages[${rowCount}][sku]" value="${pkg ? (pkg.sku||'') : ''}"></td>
                    <td><input type="number" step="0.01" class="form-control form-control-sm" name="packages[${rowCount}][weight]" value="${pkg ? (pkg.weight||'') : ''}"></td>
                    <td><input type="number" step="0.01" class="form-control form-control-sm" name="packages[${rowCount}][height]" value="${pkg ? (pkg.height||'') : ''}"></td>
                    <td><input type="number" step="0.01" class="form-control form-control-sm" name="packages[${rowCount}][width]" value="${pkg ? (pkg.width||'') : ''}"></td>
                    <td><input type="number" step="0.01" class="form-control form-control-sm" name="packages[${rowCount}][length]" value="${pkg ? (pkg.length||'') : ''}"></td>
                    <td><input type="text" class="form-control form-control-sm" name="packages[${rowCount}][barcode]" value="${pkg ? (pkg.barcode||'') : ''}"></td>
                    <td><input type="number" step="0.000001" class="form-control form-control-sm row-cf" name="packages[${rowCount}][conversion_factor]" value="${pkg ? pkg.conversion_factor : (isBase ? '1' : '')}" ${isBase ? 'readonly' : ''} required></td>
                    <td><input type="number" step="0.01" class="form-control form-control-sm row-purch" name="packages[${rowCount}][purchase_price]" value="${pkg ? pkg.purchase_price : ''}" required></td>
                    <td><input type="number" step="0.01" class="form-control form-control-sm row-sale" name="packages[${rowCount}][sale_price]" value="${pkg ? pkg.sale_price : ''}" required></td>
                    <td>
                        ${isBase ? `<button type="button" class="btn btn-sm btn-success row-tick" title="Tick to lock row"><i class="las la-check"></i></button>` : 
                                   `<button type="button" class="btn btn-sm btn-success row-tick"><i class="las la-check"></i></button>
                                    <button type="button" class="btn btn-sm btn-danger row-cross"><i class="las la-times"></i></button>`}
                    </td>
                `;
                tbody.appendChild(tr);
                
                if(isBase) {
                    syncBaseName();
                    syncBaseSymbol();
                    
                    const purchInput = tr.querySelector('.row-purch');
                    const saleInput = tr.querySelector('.row-sale');
                    
                    basePurchPrice = parseFloat(purchInput.value) || 0;
                    baseSalePrice = parseFloat(saleInput.value) || 0;
                    
                    purchInput.addEventListener('input', e => { basePurchPrice = parseFloat(e.target.value) || 0; });
                    saleInput.addEventListener('input', e => { baseSalePrice = parseFloat(e.target.value) || 0; });
                }
                
                tr.querySelector('.row-tick').addEventListener('click', function() {
                    const cfInput = tr.querySelector('.row-cf');
                    const purchInput = tr.querySelector('.row-purch');
                    const saleInput = tr.querySelector('.row-sale');
                    
                    if(!isBase) {
                        const cf = parseFloat(cfInput.value) || 1;
                        purchInput.value = (basePurchPrice * cf).toFixed(2);
                        saleInput.value = (baseSalePrice * cf).toFixed(2);
                    }
                    
                    tr.querySelectorAll('input, select').forEach(el => {
                        if(el.type !== 'hidden') el.setAttribute('readonly', 'readonly');
                        if(el.tagName === 'SELECT') el.style.pointerEvents = 'none';
                    });
                });
                
                const cross = tr.querySelector('.row-cross');
                if(cross) {
                    cross.addEventListener('click', function() {
                        tr.remove();
                    });
                }
            }
            
            function syncBaseName() {
                const baseNameInp = tbody.querySelector('tr:first-child .row-name');
                if(baseNameInp && !baseNameInp.value) baseNameInp.value = productNameInput.value;
                else if(baseNameInp && document.activeElement === productNameInput) {
                     baseNameInp.value = productNameInput.value;
                }
            }
            
            function syncBaseSymbol() {
                const baseSym = tbody.querySelector('tr:first-child .row-symbol');
                const baseSymHidden = tbody.querySelector('tr:first-child .hidden-base-symbol');
                if(baseSym && baseUomSelect.value) {
                    const val = baseUomSelect.value;
                    let opt = Array.from(baseSym.options).find(o => o.value === val);
                    if(!opt) {
                        opt = new Option(val, val);
                        baseSym.add(opt);
                    }
                    baseSym.value = val;
                    if(baseSymHidden) baseSymHidden.value = val;
                }
            }

            productNameInput.addEventListener('input', syncBaseName);
            baseUomSelect.addEventListener('change', syncBaseSymbol);

            addRowBtn.addEventListener('click', () => generateRow(null, false));
            
            if(existingPackages && existingPackages.length > 0) {
                const basePkg = existingPackages.find(p => p.is_base);
                const otherPkgs = existingPackages.filter(p => !p.is_base);
                
                if(basePkg) generateRow(basePkg, true);
                else generateRow(null, true);
                
                otherPkgs.forEach(p => generateRow(p, false));
            } else {
                generateRow(null, true);
            }

            """

if js_start != -1 and js_end != -1:
    content = content[:js_start] + new_js + '\n            ' + content[js_end:]

with open('resources/views/admin_panel/product/edit.blade.php', 'w', encoding='utf-8') as f:
    f.write(content)
