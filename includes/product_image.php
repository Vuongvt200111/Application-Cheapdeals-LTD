<?php
/* ============================================================
   Product pictures.

   A picture lives at  assets/products/<code>.<ext>  and is found by
   convention from the package/product code — nothing is stored in the
   database, so adding a picture never needs a schema change.

   An optional  <code>-thumb.<ext>  is the same artwork cropped to the
   product itself; catalogue cards prefer it so the spec chips in our
   drawn artwork do not shrink into unreadable noise.
   ============================================================ */

if (!function_exists('cd_img_dir')) {
    function cd_img_dir() { return __DIR__ . '/../assets/products'; }
}

if (!function_exists('cd_img_exts')) {
    function cd_img_exts() { return ['svg', 'png', 'jpg', 'jpeg', 'webp', 'gif']; }
}

if (!function_exists('cd_valid_code')) {
    function cd_valid_code($code) {
        return is_string($code) && $code !== '' && preg_match('/^[A-Za-z0-9_-]+$/', $code) === 1;
    }
}

if (!function_exists('cd_product_image')) {
    /* Web path of the picture for $code, or '' when it has none. */
    function cd_product_image($code) {
        if (!cd_valid_code($code)) return '';
        foreach (cd_img_exts() as $e) {
            if (is_file(cd_img_dir() . '/' . $code . '.' . $e)) {
                return 'assets/products/' . $code . '.' . $e;
            }
        }
        return '';
    }
}

if (!function_exists('cd_resolve_artwork_code')) {
    function cd_resolve_artwork_code($code, $category = '') {
        $c = strtolower(trim((string)$code));
        $cat = strtolower(trim((string)$category));
        
        if (!empty($c)) {
            foreach (['svg', 'png', 'jpg', 'jpeg', 'webp', 'gif'] as $e) {
                if (is_file(__DIR__ . '/../assets/products/' . $c . '.' . $e) || is_file(__DIR__ . '/../assets/products/' . $c . '-thumb.' . $e)) {
                    return $c;
                }
            }
        }
        
        $map = [
            'mob-lite' => 'mob-pro',
            'mobile' => 'mob-pro',
            'mob-pro' => 'mob-pro',
            'bb-lite' => 'bb-lite',
            'broadband' => 'bb-lite',
            'bb-pro' => 'bb-lite',
            'tab-lite' => 'prod-s24',
            'tablet' => 'prod-s24',
            'double' => 'prod-laptop',
            'triple' => 'prod-desktop',
            'prod-laptop' => 'prod-laptop',
            'prod-desktop' => 'prod-desktop',
            'prod-keyboard' => 'prod-keyboard',
            'prod-mouse' => 'prod-mouse',
            'prod-cable' => 'prod-cable',
            'prod-iphone15' => 'prod-iphone15',
            'prod-s24' => 'prod-s24',
            'prod-reno11' => 'prod-reno11'
        ];
        
        if (isset($map[$c])) return $map[$c];
        
        if (strpos($cat, 'mobile') !== false || strpos($cat, 'data') !== false) return 'mob-pro';
        if (strpos($cat, 'broadband') !== false) return 'bb-lite';
        if (strpos($cat, 'tablet') !== false) return 'prod-s24';
        if (strpos($cat, 'bundle') !== false) return 'prod-laptop';
        if (strpos($cat, 'hardware') !== false) return 'prod-desktop';
        
        return 'mob-pro';
    }
}

if (!function_exists('cd_product_card_image')) {
    /* Cropped card variant when one exists, otherwise the full picture. */
    function cd_product_card_image($code, $category = '') {
        $realCode = cd_resolve_artwork_code($code, $category);
        $thumb = cd_product_image($realCode . '-thumb');
        return $thumb !== '' ? $thumb : cd_product_image($realCode);
    }
}

if (!function_exists('cd_delete_product_image')) {
    /* Remove every picture file belonging to $code (all extensions + thumb). */
    function cd_delete_product_image($code) {
        if (!cd_valid_code($code)) return;
        foreach (cd_img_exts() as $e) {
            foreach ([$code . '.' . $e, $code . '-thumb.' . $e] as $name) {
                $p = cd_img_dir() . '/' . $name;
                if (is_file($p)) @unlink($p);
            }
        }
    }
}

if (!function_exists('cd_image_library')) {
    /* [code => display name] for every item that already has a picture, used
       to offer "reuse the picture of an existing product". */
    function cd_image_library($pdo = null) {
        $out = [];
        if (!is_dir(cd_img_dir())) return $out;

        $names = [];
        if ($pdo) {
            try {
                foreach ($pdo->query('SELECT code,name FROM packages') as $r) $names[$r['code']] = $r['name'];
                foreach ($pdo->query('SELECT code,name FROM products') as $r) $names[$r['code']] = $r['name'];
            } catch (Throwable $e) { /* library still works without names */ }
        }

        foreach ((array)glob(cd_img_dir() . '/*.*') as $file) {
            $base = basename($file);
            $ext  = strtolower(pathinfo($base, PATHINFO_EXTENSION));
            if (!in_array($ext, cd_img_exts(), true)) continue;
            $code = substr($base, 0, -(strlen($ext) + 1));
            if (substr($code, -6) === '-thumb') continue;   // variant, not an entry
            if (!cd_valid_code($code)) continue;
            $out[$code] = isset($names[$code]) ? $names[$code] : $code;
        }
        asort($out, SORT_NATURAL | SORT_FLAG_CASE);
        return $out;
    }
}

if (!function_exists('cd_strip_svg_caption')) {
    /* Our drawn artwork carries the product name and price in the bottom-left
       corner. When that artwork is reused for a different product those two
       lines would be wrong, so they are removed from the copy. The brand line
       (also x=72, but near the top) is deliberately kept. */
    function cd_strip_svg_caption($svg) {
        $out = preg_replace_callback('~<text\b[^>]*>.*?</text>~is', function ($m) {
            if (preg_match('/\bx="(\d+(?:\.\d+)?)"/', $m[0], $mx)
             && preg_match('/\by="(\d+(?:\.\d+)?)"/', $m[0], $my)
             && (float)$mx[1] < 200 && (float)$my[1] > 300) {
                return '';
            }
            return $m[0];
        }, $svg);
        return $out === null ? $svg : $out;
    }
}

if (!function_exists('cd_save_uploaded_image')) {
    /* Store an uploaded picture as the artwork for $code.
       Returns '' on success (or when nothing was uploaded), else an error
       message suitable for showing to staff. */
    function cd_save_uploaded_image($code, $field = 'image') {
        if (empty($_FILES[$field])) return '';
        $f = $_FILES[$field];
        $err = $f['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($err === UPLOAD_ERR_NO_FILE) return '';                    // staff left it blank
        if (!cd_valid_code($code)) return 'Invalid product code.';
        if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) return 'The picture is too large for the server upload limit.';
        if ($err !== UPLOAD_ERR_OK) return 'The picture could not be uploaded (error ' . (int)$err . ').';
        if (!is_uploaded_file($f['tmp_name'])) return 'The upload was rejected.';
        if ((int)$f['size'] > 3 * 1024 * 1024) return 'The picture is larger than 3 MB.';

        $ext = strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION));
        if ($ext === 'jpe' || $ext === 'jfif') $ext = 'jpeg';
        if (!in_array($ext, cd_img_exts(), true)) {
            return 'Only SVG, PNG, JPG, WEBP or GIF pictures are accepted.';
        }

        if ($ext === 'svg') {
            $svg = (string)@file_get_contents($f['tmp_name']);
            if (stripos($svg, '<svg') === false) return 'That file is not a valid SVG drawing.';
            /* An SVG opened directly runs in this site's origin, so anything
               executable is refused rather than cleaned up. */
            if (preg_match('~<script|javascript:|\son\w+\s*=|<foreignObject|<iframe|<embed|<object|<!ENTITY~i', $svg)) {
                return 'That SVG contains scripting or embedded content and was rejected for security.';
            }
        } else {
            $info = @getimagesize($f['tmp_name']);
            if ($info === false) return 'That file is not a readable image.';
            $allowed = [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF];
            if (defined('IMAGETYPE_WEBP')) $allowed[] = IMAGETYPE_WEBP;
            if (!in_array($info[2], $allowed, true)) return 'That image format is not supported.';
        }

        if (!is_dir(cd_img_dir()) && !@mkdir(cd_img_dir(), 0777, true)) {
            return 'The pictures folder could not be created.';
        }

        cd_delete_product_image($code);        // a product keeps one picture
        $dest = cd_img_dir() . '/' . $code . '.' . $ext;
        if (!@move_uploaded_file($f['tmp_name'], $dest)) return 'The picture could not be saved to disk.';
        return '';
    }
}

if (!function_exists('cd_copy_product_image')) {
    /* Reuse the picture of $fromCode for $toCode. Returns '' on success,
       otherwise an error message. */
    function cd_copy_product_image($fromCode, $toCode) {
        if ($fromCode === '' || $fromCode === null) return '';
        if (!cd_valid_code($fromCode) || !cd_valid_code($toCode)) return 'Invalid product code.';
        if ($fromCode === $toCode) return '';

        $src = cd_product_image($fromCode);
        if ($src === '') return 'That product has no picture to copy.';

        $ext  = strtolower(pathinfo($src, PATHINFO_EXTENSION));
        $from = cd_img_dir() . '/' . $fromCode . '.' . $ext;

        cd_delete_product_image($toCode);

        if ($ext === 'svg') {
            $svg = (string)@file_get_contents($from);
            if ($svg === '') return 'That picture could not be read.';
            if (@file_put_contents(cd_img_dir() . '/' . $toCode . '.svg', cd_strip_svg_caption($svg)) === false) {
                return 'The picture could not be copied.';
            }
        } elseif (!@copy($from, cd_img_dir() . '/' . $toCode . '.' . $ext)) {
            return 'The picture could not be copied.';
        }

        /* Carry the cropped card variant across as well, when there is one. */
        foreach (cd_img_exts() as $e) {
            $t = cd_img_dir() . '/' . $fromCode . '-thumb.' . $e;
            if (!is_file($t)) continue;
            if ($e === 'svg') {
                $tsvg = (string)@file_get_contents($t);
                if ($tsvg !== '') @file_put_contents(cd_img_dir() . '/' . $toCode . '-thumb.svg', cd_strip_svg_caption($tsvg));
            } else {
                @copy($t, cd_img_dir() . '/' . $toCode . '-thumb.' . $e);
            }
            break;
        }
        return '';
    }
}

if (!function_exists('cd_apply_product_image')) {
    /* One entry point for the staff forms: an uploaded file wins, otherwise the
       picture of the chosen existing product is reused. Returns '' when there
       is nothing to report, or an error message. $changed is set to true only
       when a picture was actually written. */
    function cd_apply_product_image($code, &$changed = false, $fileField = 'image', $copyField = 'image_from') {
        $changed = false;

        $uploaded = !empty($_FILES[$fileField])
                 && ($_FILES[$fileField]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        if ($uploaded) {
            $err = cd_save_uploaded_image($code, $fileField);
            if ($err !== '') return $err;
            $changed = true;
            return '';
        }

        $from = trim((string)($_POST[$copyField] ?? ''));
        if ($from === '') return '';                 // staff chose neither option
        $err = cd_copy_product_image($from, $code);
        if ($err !== '') return $err;
        $changed = true;
        return '';
    }
}
