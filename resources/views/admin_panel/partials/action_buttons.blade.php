@php
    // Inputs:
    //   editRoute (string|null) - URL used for edit action (link or data-url for JS)
    //   deleteRoute (string|null) - URL for delete action
    //   editIsLink (bool) - render edit as <a> link when true, otherwise a button with class `edit-btn` and data-url
    //   permissions (array) - ['edit' => 'permission.name', 'delete' => 'permission.name']
    //   editLabel, deleteLabel - optional labels
    //   dataId - optional id to render as data-id on buttons

    $editPerm = $permissions['edit'] ?? null;
    $deletePerm = $permissions['delete'] ?? null;
    $canEdit = auth()->check() && $editPerm ? auth()->user()->can($editPerm) : false;
    $canDelete = auth()->check() && $deletePerm ? auth()->user()->can($deletePerm) : false;
    $dataIdAttr = isset($dataId) ? 'data-id="'.e($dataId).'"' : '';
@endphp

@if($canEdit)
    @if(!empty($editIsLink))
        <a href="{{ $editRoute }}" class="btn btn-sm btn-outline-primary">{{ $editLabel ?? 'Edit' }}</a>
    @else
        <button class="btn btn-primary btn-sm edit-btn" {!! $dataIdAttr !!} data-url="{{ $editRoute }}">{{ $editLabel ?? 'Edit' }}</button>
    @endif
@endif

@if($canDelete)
    <button class="btn btn-danger btn-sm delete-btn" {!! $dataIdAttr !!} data-url="{{ $deleteRoute }}" data-msg="{{ $deleteMsg ?? 'Are you sure?' }}" data-method="get" onclick="logoutAndDeleteFunction(this)">{{ $deleteLabel ?? 'Delete' }}</button>
@endif
