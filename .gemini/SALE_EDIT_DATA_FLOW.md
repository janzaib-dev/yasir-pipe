# Sale Edit - Complete Data Flow Verification

## ✅ Database Structure (sale_items table)

The `sale_items` table contains all necessary columns:
- `id` - Primary key
- `sale_id` - Foreign key to sales table
- **`warehouse_id`** - Stores which warehouse the product was sold from
- **`product_id`** - Stores which product was sold
- `brand_id`, `category_id`, `sub_category_id`, `unit_id` - Product metadata
- `qty` - Boxes (for backward compatibility)
- **`total_pieces`** - Total pieces sold
- **`loose_pieces`** - Loose pieces
- `price` - Retail/Box price
- `price_per_piece` - Calculated price per piece
- `discount_percent` - Discount percentage
- `discount_amount` - Discount amount
- `total` - Line total
- `color` - Color data (JSON)

## ✅ Backend Data Retrieval (_getSaleItems method)

When editing a sale, the `SaleController::_getSaleItems()` method returns:

```php
[
    'product_id' => $item->product_id,           // ✅ Product ID
    'warehouse_id' => $item->warehouse_id,       // ✅ Warehouse ID
    'warehouse_name' => $warehouse->warehouse_name, // ✅ Warehouse Name
    'item_name' => $product->item_name,          // ✅ Product Name
    'pieces_per_box' => $piecesPerBox,           // ✅ Pack Size
    'boxes' => $boxes,                            // ✅ Calculated Boxes
    'loose_pieces' => $loosePieces,              // ✅ Loose Pieces
    'qty' => $item->total_pieces,                // ✅ Total Pieces
    'price' => $item->price,                     // ✅ Retail Price
    'price_per_piece' => $calculated,            // ✅ Price per Piece
    'discount' => $item->discount_percent,       // ✅ Discount %
    'discount_amount' => $calculated,            // ✅ Discount Amount
    'total' => $item->total,                     // ✅ Line Total
]
```

## ✅ Frontend Population (addPopulatedRow function)

The JavaScript function populates ALL fields:

### Product & Warehouse Selection
```javascript
// 1. Add product to Select2
const option = new Option(item.item_name, item.product_id, true, true);
$prod.append(option);

// 2. Trigger change with warehouse info
$prod.trigger('change', [item.warehouse_id, item.warehouse_name]);
// This triggers the product change handler which:
//   - Loads warehouses for this product
//   - Auto-selects the warehouse_id
//   - If warehouse not in list, forcefully adds it
```

### Quantity Fields
```javascript
$row.find('.sales-qty').val(item.qty);              // Total Pieces
$row.find('.pack-size').val(item.pieces_per_box);  // Pack Size
$row.find('.loose-pieces').val(item.loose_pieces); // Loose Pieces
$row.find('.boxes').val(item.boxes);                // Boxes
```

### Pricing Fields
```javascript
$row.find('.retail-price').val(item.price);              // Retail Price
$row.find('.price-per-piece').val(item.price_per_piece); // Price/Piece
```

### Discount Fields
```javascript
$row.find('.discount-value').val(item.discount);          // Discount %
$row.find('.discount-amount').val(item.discount_amount);  // Discount Amt
```

### Stock Field
```javascript
// Stock is auto-populated by the warehouse change event
// When warehouse is selected, it updates stock from data-stock attribute
```

## ✅ Product Change Handler

When a product is selected (or pre-selected during edit):

```javascript
$(document).on('change', '.product', function(e, manualWhId, manualWhName) {
    // 1. Load warehouses for this product
    $.get('/warehouses/get', { product_id: productId })
    
    // 2. Build warehouse dropdown
    warehouses.forEach(w => {
        html += `<option value="${w.warehouse_id}" data-stock="${w.stock}">
                   ${w.warehouse_name} -- ${w.stock}
                 </option>`;
    });
    
    // 3. If manual warehouse not in list, add it
    if (manualWhId && !found) {
        html += `<option value="${manualWhId}" data-stock="0">
                   ${manualWhName} (History)
                 </option>`;
    }
    
    // 4. Auto-select the warehouse
    $warehouse.val(manualWhId).trigger('change');
});
```

## ✅ Warehouse Change Handler

When warehouse is selected:

```javascript
$(document).on('change', '.warehouse', function() {
    // Update stock from selected warehouse
    const stock = $(this).find(':selected').data('stock') || 0;
    $row.find('.stock').val(stock);
});
```

## ✅ Form Submission

When the form is submitted, each row sends:

```html
<select name="product_id[]">...</select>        <!-- Product ID -->
<select name="warehouse_id[]">...</select>      <!-- Warehouse ID -->
<input name="qty[]">                            <!-- Total Pieces -->
<input name="pack_qty[]">                       <!-- Pack Size -->
<input name="loose_pieces[]">                   <!-- Loose Pieces -->
<input name="price[]">                          <!-- Retail Price -->
<input name="item_disc[]">                      <!-- Discount % -->
<input name="total[]">                          <!-- Line Total -->
```

## ✅ Backend Save (SaleController::_processSaleSave)

```php
foreach ($p_ids as $idx => $pid) {
    SaleItem::create([
        'sale_id' => $model->id,
        'product_id' => $pid,                              // ✅ Saved
        'warehouse_id' => $request->warehouse_id[$idx],    // ✅ Saved
        'total_pieces' => $totalPieces,                    // ✅ Saved
        'qty' => $boxes,                                   // ✅ Saved
        'loose_pieces' => $loose,                          // ✅ Saved
        'price' => $request->price[$idx],                  // ✅ Saved
        'discount_percent' => $request->item_disc[$idx],   // ✅ Saved
        'total' => $request->total[$idx],                  // ✅ Saved
        // ... other fields
    ]);
}
```

## 🎯 Complete Flow Summary

### When Editing a Sale:

1. **Load Sale Data** → `SaleController::saleedit($id)`
2. **Fetch Items** → `_getSaleItems($sale)` returns complete item data
3. **Pass to View** → `booking_edit.blade.php` receives `$saleItems`
4. **Initialize Form** → `init()` function calls `addPopulatedRow()` for each item
5. **Populate Row** → All fields are filled with saved data
6. **Load Warehouses** → Product change triggers warehouse loading
7. **Auto-select Warehouse** → Warehouse is pre-selected using `manualWhId`
8. **Update Stock** → Warehouse change updates stock display
9. **User Edits** → User can modify any field
10. **Submit Form** → All data including `warehouse_id` and `product_id` sent
11. **Save to DB** → `SaleItem` records created/updated with all data

## ✅ All Requirements Met

- ✅ Warehouses loaded based on selected product
- ✅ Stock updated based on selected warehouse  
- ✅ Pack size (pieces_per_box) loaded from product
- ✅ Boxes calculated automatically
- ✅ Warehouse auto-selected to saved warehouse_id
- ✅ Product auto-selected to saved product_id
- ✅ All data saved in sale_items table (warehouse_id, product_id, etc.)
- ✅ All fields populated when editing
