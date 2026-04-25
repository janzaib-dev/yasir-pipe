# Purchase Return UI/UX - Minimalist Update

## Summary
Refined the Purchase Return interface to be cleaner and more minimal, removing heavy visual noise while maintaining the core functionality of correct cursor blocking.

## Changes Made

### 1. **Minimalist Visuals**
- **Read-Only Fields**:
  - Removed striped/diagonal gradient backgrounds.
  - Changed background to a subtle, flat light gray (`#f9fafb`).
  - Removed pulsing animations from lock icons.
  - Used standard borders (`var(--erp-border)`) instead of differentiated colors.

- **Editable Fields**:
  - Removed permanent green borders and backgrounds.
  - Fields now look standard (white background) and only highlight with a subtle primary color ring on focus.
  - "Return Qty" column header is now standard gray, blending with other headers.

- **General Cleanup**:
  - Removed the large "Information" alert box to reduce screen clutter.
  - Simplified the `box-shadow` on cards for a flatter, modern look.

### 2. **Functionality Maintained**
- **Cursor Blocking**:
  - `pointer-events: none` and `cursor: not-allowed` are still applied to read-only fields to strictly prevent interaction.
- **Visual Cues**:
  - Lock icons remain on read-only labels/headers but are static and varying shades of gray (subtle).

## CSS Highlights

```css
/* Minimal Read-Only Style */
.form-control[readonly] {
    background-color: #f9fafb; /* Very subtle gray */
    color: var(--erp-muted);
    cursor: not-allowed;
    pointer-events: none;
    border-color: var(--erp-border);
}

/* Minimal Editable Focus */
.quantity-box:not([readonly]):focus {
    border-color: var(--erp-primary) !important;
    box-shadow: 0 0 0 3px rgba(74, 105, 189, 0.1) !important;
}
```

## Files Modified
- `resources/views/admin_panel/purchase/purchase_return/create.blade.php`
