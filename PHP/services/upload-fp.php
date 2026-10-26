<?php
//validate and store pdf files
const FLOORPLAN_DIR = __DIR__ . '/../storage/floorplans';
const MAX_PDF_BYTES = 20 * 1024 * 1024; // 20 MB per file

function normalize_uploads(array $field): array
{
    $files = [];
    if (!isset($field['name']) || !is_array($field['name'])) {
        return $files;
    }
    foreach ($field['name'] as $i => $name) {
        if ($field['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $files[] = [
            'name'     => $name,
            'tmp_name' => $field['tmp_name'][$i],
            'error'    => $field['error'][$i],
            'size'     => $field['size'][$i],
        ];
    }
    return $files;
}

function validate_floorplans(array $files): string
{
    if (count($files) === 0) {
        return 'Upload at least one floorplan PDF.';
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    foreach ($files as $f) {
        if ($f['error'] !== UPLOAD_ERR_OK) {
            return 'Could not upload "' . $f['name'] . '". The file may be larger than the server allows.';
        }
        if ($f['size'] > MAX_PDF_BYTES) {
            return '"' . $f['name'] . '" is larger than 20 MB.';
        }
        if ($finfo->file($f['tmp_name']) !== 'application/pdf') {
            return '"' . $f['name'] . '" is not a PDF.';
        }
    }
    return ''; //no errors
}


function store_floorplans(array $files): array
{
    if (!is_dir(FLOORPLAN_DIR)) {
        mkdir(FLOORPLAN_DIR, 0775, true);
    }
    $saved = [];
    foreach ($files as $f) {
        $stored = bin2hex(random_bytes(16)) . '.pdf';
        if (!move_uploaded_file($f['tmp_name'], FLOORPLAN_DIR . '/' . $stored)) {
            delete_stored_floorplans($saved);
            throw new RuntimeException('Could not save the uploaded file.');
        }
        $saved[] = $stored;
    }
    return $saved;
}

function delete_stored_floorplans(array $paths): void
{
    foreach ($paths as $p) {
        @unlink(FLOORPLAN_DIR . '/' . $p);
    }
}
