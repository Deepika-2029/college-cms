<?php

namespace App\Services;

/**
 * ComponentRenderer — renders built-in component types from content arrays to HTML.
 */
class ComponentRenderer
{
    private static array $builtIn = [
        // Original
        'text','image','button','hero','card','testimonial','stats','accordion',
        'divider','spacer','gallery','video','embed',
        // New built-ins
        'load-more-gallery','notices','counter','timeline',
        'team-grid','price-table','cta-banner','map-embed',
        // Builder v5 types (from state.js DEF)
        'navbar','heading','richtext','cards','testimonials','cta','footer',
        'pricing','contact-form','section','cols2','cols3','aside-left','aside-right',
    ];

    public static function isBuiltIn(string $type): bool
    {
        return in_array($type, self::$builtIn, true);
    }

    public function render(string $type, array $content, string $cellId = '', array $tree = [], ?callable $recursiveRenderer = null): string
    {
        switch ($type) {
            // ── Legacy / original ──
            case 'text':              return $this->text($content, $cellId);
            case 'image':             return $this->image($content, $cellId);
            case 'button':            return $this->button($content, $cellId);
            case 'hero':              return $this->hero($content, $cellId);
            case 'card':              return $this->card($content, $cellId);
            case 'testimonial':       return $this->testimonial($content, $cellId);
            case 'stats':             return $this->builderStats($content, $cellId);
            case 'accordion':         return $this->accordion($content, $cellId);
            case 'divider':           return $this->divider($content, $cellId);
            case 'spacer':            return $this->spacer($content, $cellId);
            case 'gallery':           return $this->gallery($content, $cellId);
            case 'video':             return $this->video($content, $cellId);
            case 'embed':             return $this->embed($content, $cellId);
            case 'load-more-gallery': return $this->loadMoreGallery($content, $cellId);
            case 'counter':           return $this->counter($content, $cellId);
            case 'timeline':          return $this->timeline($content, $cellId);
            case 'team-grid':         return $this->teamGrid($content, $cellId);
            case 'price-table':       return $this->priceTable($content, $cellId);
            case 'cta-banner':        return $this->ctaBanner($content, $cellId);
            case 'map-embed':         return $this->mapEmbed($content, $cellId);
            // ── Builder v5 types (state.js DEF) ──
            case 'navbar':            return $this->builderNavbar($content, $cellId);
            case 'heading':           return $this->builderHeading($content, $cellId);
            case 'richtext':          return $this->builderRichtext($content, $cellId);
            case 'cards':             return $this->builderCards($content, $cellId);
            case 'testimonials':      return $this->builderTestimonials($content, $cellId);
            case 'cta':               return $this->builderCta($content, $cellId);
            case 'footer':            return $this->builderFooter($content, $cellId);
            case 'notices':           return $this->builderNotices($content, $cellId);
            case 'pricing':           return $this->builderPricing($content, $cellId);
            case 'contact-form':      return $this->builderContactForm($content, $cellId);
            case 'section':           return $this->builderSection($content, $cellId, $tree, $recursiveRenderer);
            case 'cols2': case 'cols3':
            case 'aside-left': case 'aside-right':
                                      return $this->builderLayout($type, $content, $cellId, $tree, $recursiveRenderer);
            case 'nav-bar':           return '';
            case 'cms-inline-html':   return $content['__html'] ?? '';
            default:                  return $this->plugin($type, $content, $cellId);
        }
    }

    // ── Built-in components ───────────────────────────────────────────────

    private function text(array $c, string $id): string
    {
        $html  = $c['text']  ?? '';
        $align = $c['align'] ?? 'left';
        $color = $c['color'] ?? '';
        $style = '';
        if ($color) $style .= "color:{$color};";
        if ($align !== 'left') $style .= "text-align:{$align};";
        $sa = $style ? " style=\"{$style}\"" : '';
        return "<div class=\"cms-comp-text\" id=\"{$id}\"{$sa}>{$html}</div>";
    }

    private function image(array $c, string $id): string
    {
        $src  = htmlspecialchars($c['src'] ?? '', ENT_QUOTES);
        $alt  = htmlspecialchars($c['alt'] ?? '', ENT_QUOTES);
        $cap  = $c['caption'] ?? '';
        $link = $c['link'] ?? '';
        $rounded = ($c['rounded'] ?? true)  ? 'border-radius:8px;' : '';
        $shadow  = ($c['shadow']  ?? false) ? 'box-shadow:0 4px 20px rgba(0,0,0,.12);' : '';

        if (! $src) {
            return "<figure class=\"cms-comp-image\" id=\"{$id}\"><div style=\"aspect-ratio:16/9;background:#f1f5f9;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:2rem;\">📷</div></figure>";
        }
        $img = "<img src=\"{$src}\" alt=\"{$alt}\" loading=\"lazy\" style=\"width:100%;height:auto;{$rounded}{$shadow}\" />";
        $inner = $link ? "<a href=\"" . htmlspecialchars($link, ENT_QUOTES) . "\">{$img}</a>" : $img;
        $capHtml = $cap ? "<figcaption>" . htmlspecialchars($cap) . "</figcaption>" : '';
        return "<figure class=\"cms-comp-image\" id=\"{$id}\">{$inner}{$capHtml}</figure>";
    }

    private function button(array $c, string $id): string
    {
        $text   = htmlspecialchars($c['text']  ?? 'Click Here');
        $url    = htmlspecialchars($c['url']   ?? '#', ENT_QUOTES);
        $style  = $c['style']  ?? 'primary';
        $size   = $c['size']   ?? '';
        $align  = $c['align']  ?? 'left';
        $target = ($c['new_tab'] ?? false) ? ' target="_blank" rel="noopener"' : '';
        $icon   = $c['icon']   ?? '';
        $cls    = 'cms-btn cms-btn-' . $style . ($size ? ' cms-btn-' . $size : '');
        return "<div class=\"cms-comp-button\" id=\"{$id}\" style=\"text-align:{$align};\"><a href=\"{$url}\" class=\"{$cls}\"{$target}>{$icon}{$text}</a></div>";
    }

    private function hero(array $c, string $id): string
    {
        $headline = htmlspecialchars($c['headline']    ?? 'Welcome');
        $sub      = htmlspecialchars($c['subheadline'] ?? '');
        $bg       = htmlspecialchars($c['bg_color']    ?? '#1e3a5f', ENT_QUOTES);
        $tc       = htmlspecialchars($c['text_color']  ?? '#ffffff', ENT_QUOTES);
        $bgImg    = $c['bg_image']   ?? '';
        $minH     = $c['min_height'] ?? '400px';
        $bgStyle  = $bgImg ? "background:url('{$bgImg}') center/cover no-repeat;background-color:{$bg};" : "background:{$bg};";

        $ctaHtml = '';
        foreach ($c['buttons'] ?? [] as $btn) {
            $bStyle = $btn['style'] ?? 'primary';
            $bUrl   = htmlspecialchars($btn['url']  ?? '#', ENT_QUOTES);
            $bText  = htmlspecialchars($btn['text'] ?? 'Learn More');
            $ctaHtml .= "<a href=\"{$bUrl}\" class=\"cms-btn cms-btn-{$bStyle}\">{$bText}</a>\n";
        }
        $subHtml = $sub ? "<p>{$sub}</p>" : '';
        $ctaWrap = $ctaHtml ? "<div class=\"hero-cta\">{$ctaHtml}</div>" : '';

        return <<<HTML
<div class="cms-comp-hero" id="{$id}" style="{$bgStyle}color:{$tc};min-height:{$minH};">
    <h1>{$headline}</h1>
    {$subHtml}
    {$ctaWrap}
</div>
HTML;
    }

    private function card(array $c, string $id): string
    {
        $img    = $c['image']       ?? '';
        $title  = htmlspecialchars($c['title']       ?? 'Card Title');
        $text   = htmlspecialchars($c['text']        ?? '');
        $btnTxt = $c['button_text'] ?? '';
        $btnUrl = htmlspecialchars($c['button_url']  ?? '#', ENT_QUOTES);

        $imgHtml = $img ? "<img src=\"{$img}\" class=\"card-image\" alt=\"" . htmlspecialchars($c['image_alt'] ?? '') . "\" loading=\"lazy\" />" : '';
        $btnHtml = $btnTxt ? "<a href=\"{$btnUrl}\" class=\"cms-btn cms-btn-primary cms-btn-sm\" style=\"margin-top:auto;align-self:flex-start;\">" . htmlspecialchars($btnTxt) . "</a>" : '';

        return <<<HTML
<div class="cms-comp-card" id="{$id}">
    {$imgHtml}
    <div class="card-body">
        <div class="card-title">{$title}</div>
        <p class="card-text">{$text}</p>
        {$btnHtml}
    </div>
</div>
HTML;
    }

    private function testimonial(array $c, string $id): string
    {
        $quote  = htmlspecialchars($c['quote']  ?? 'A great experience.');
        $name   = htmlspecialchars($c['name']   ?? '');
        $role   = htmlspecialchars($c['role']   ?? '');
        $avatar = $c['avatar'] ?? '';
        $rating = (int) ($c['rating'] ?? 5);
        $stars  = str_repeat('★', max(0, min(5, $rating))) . str_repeat('☆', 5 - max(0, min(5, $rating)));

        $avatarHtml = $avatar
            ? "<img src=\"{$avatar}\" class=\"author-avatar\" alt=\"{$name}\" />"
            : "<div class=\"author-avatar\">👤</div>";

        return <<<HTML
<div class="cms-comp-testimonial" id="{$id}">
    <div class="stars">{$stars}</div>
    <p class="quote">{$quote}</p>
    <div class="author">
        {$avatarHtml}
        <div>
            <div class="author-name">{$name}</div>
            <div class="author-role">{$role}</div>
        </div>
    </div>
</div>
HTML;
    }

    private function stats(array $c, string $id): string
    {
        $items = $c['items'] ?? [
            ['number' => '15K+', 'label' => 'Students'],
            ['number' => '500+', 'label' => 'Faculty'],
            ['number' => '80+',  'label' => 'Programs'],
            ['number' => '#12',  'label' => 'Ranked'],
        ];
        $color = $c['color'] ?? '#6366f1';

        $html = '';
        foreach ($items as $item) {
            $num   = htmlspecialchars($item['number'] ?? '0');
            $label = htmlspecialchars($item['label']  ?? '');
            $html .= "<div><div class=\"stat-num\" style=\"color:{$color};\">{$num}</div><div class=\"stat-label\">{$label}</div></div>\n";
        }

        return "<div class=\"cms-comp-stats\" id=\"{$id}\"><div class=\"stats-grid\">{$html}</div></div>";
    }

    private function accordion(array $c, string $id): string
    {
        $items = $c['items'] ?? [
            ['q' => 'What programs do you offer?', 'a' => 'We offer over 80 undergraduate and postgraduate programs.'],
            ['q' => 'How do I apply?',              'a' => 'Visit our admissions page and fill out the online application form.'],
        ];

        $html = '';
        foreach ($items as $i => $item) {
            $q = htmlspecialchars($item['q'] ?? $item['question'] ?? '');
            $a = htmlspecialchars($item['a'] ?? $item['answer']   ?? '');
            $html .= <<<ITEM
<div class="cms-accordion-item" id="{$id}-item-{$i}">
    <button class="cms-accordion-trigger" onclick="this.parentElement.classList.toggle('open')">
        {$q} <span class="acc-icon">+</span>
    </button>
    <div class="cms-accordion-body">{$a}</div>
</div>
ITEM;
        }
        return "<div class=\"cms-comp-accordion\" id=\"{$id}\">{$html}</div>";
    }

    private function divider(array $c, string $id): string
    {
        $color  = htmlspecialchars($c['color']  ?? '#e2e8f0', ENT_QUOTES);
        $height = $c['height'] ?? '2px';
        $margin = $c['margin'] ?? '0';
        return "<div class=\"cms-comp-divider\" id=\"{$id}\" style=\"margin:{$margin};\"><hr style=\"border:none;border-top:{$height} solid {$color};\" /></div>";
    }

    private function spacer(array $c, string $id): string
    {
        return "<div class=\"cms-comp-spacer\" id=\"{$id}\" style=\"height:" . ($c['height'] ?? '2rem') . ";\"></div>";
    }

    private function gallery(array $c, string $id): string
    {
        $images = $c['images'] ?? [];
        $cols   = (int) ($c['cols'] ?? $c['columns'] ?? 3);
        if (empty($images)) {
            return "<div class=\"cms-comp-gallery\" id=\"{$id}\"><div style=\"padding:2rem;text-align:center;color:#94a3b8;\">📷 No images added yet</div></div>";
        }
        $imgs = '';
        foreach ($images as $img) {
            // Support both flat URL strings and {src, alt} objects
            if (is_string($img)) {
                $src = htmlspecialchars($img, ENT_QUOTES);
                $alt = '';
            } else {
                $src = htmlspecialchars($img['src'] ?? '', ENT_QUOTES);
                $alt = htmlspecialchars($img['alt'] ?? '', ENT_QUOTES);
            }
            if (!$src) continue;
            $imgs .= "<img src=\"{$src}\" alt=\"{$alt}\" loading=\"lazy\" />\n";
        }
        return "<div class=\"cms-comp-gallery\" id=\"{$id}\"><div class=\"gallery-grid cols-{$cols}\">{$imgs}</div></div>";
    }

    private function video(array $c, string $id): string
    {
        $src = $c['src'] ?? '';
        $cap = htmlspecialchars($c['caption'] ?? '');
        if (! $src) {
            return "<div class=\"cms-comp-video\" id=\"{$id}\"><div style=\"aspect-ratio:16/9;background:#0f172a;border-radius:10px;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.3);font-size:2rem;\">▶</div></div>";
        }
        if (str_contains($src, 'youtube.com') || str_contains($src, 'youtu.be')) {
            preg_match('/(?:v=|\/embed\/|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $src, $m);
            $embed = isset($m[1]) ? "https://www.youtube-nocookie.com/embed/{$m[1]}?rel=0" : $src;
            $inner = "<iframe src=\"{$embed}\" allow=\"accelerometer;autoplay;encrypted-media;picture-in-picture\" allowfullscreen></iframe>";
        } elseif (str_contains($src, 'vimeo.com')) {
            preg_match('/vimeo\.com\/(\d+)/', $src, $m);
            $embed = isset($m[1]) ? "https://player.vimeo.com/video/{$m[1]}" : $src;
            $inner = "<iframe src=\"{$embed}\" allow=\"autoplay;fullscreen;picture-in-picture\" allowfullscreen></iframe>";
        } else {
            $inner = "<video src=\"{$src}\" controls style=\"width:100%;border-radius:10px;\"></video>";
        }
        $capHtml = $cap ? "<p style=\"font-size:.8rem;color:#64748b;text-align:center;margin-top:.5rem;\">{$cap}</p>" : '';
        return "<div class=\"cms-comp-video\" id=\"{$id}\"><div class=\"video-wrap\">{$inner}</div>{$capHtml}</div>";
    }

    private function embed(array $c, string $id): string
    {
        return "<div class=\"cms-comp-embed\" id=\"{$id}\">" . ($c['html'] ?? '') . "</div>";
    }

    // ═══════════════════════════════════════════════════════════════
    // Builder v5 component renderers
    // ═══════════════════════════════════════════════════════════════

    private function builderNavbar(array $c, string $id): string
    {
        $siteName = htmlspecialchars($c['site_name'] ?? 'My College', ENT_QUOTES);
        $logo     = $c['logo'] ?? '';
        $bg       = htmlspecialchars($c['bg'] ?? '#0f172a', ENT_QUOTES);
        $accent   = htmlspecialchars($c['accent'] ?? '#6366f1', ENT_QUOTES);
        $logoHtml = $logo
            ? "<img src=\"" . htmlspecialchars($logo, ENT_QUOTES) . "\" alt=\"" . htmlspecialchars($siteName) . "\" style=\"height:36px;object-fit:contain;\" />"
            : "<span style=\"font-size:1.2rem;font-weight:800;color:white;\">{$siteName}</span>";

        $links = $c['links'] ?? [];
        $linksHtml = '';
        foreach ($links as $link) {
            $lt = htmlspecialchars($link['label'] ?? $link['text'] ?? '', ENT_QUOTES);
            $lh = htmlspecialchars($link['url'] ?? '#', ENT_QUOTES);
            $linksHtml .= "<a href=\"{$lh}\" style=\"color:rgba(255,255,255,.8);text-decoration:none;font-size:.9rem;font-weight:500;padding:.4rem .7rem;border-radius:6px;transition:background .15s;\">{$lt}</a>";
        }
        if (!$linksHtml) {
            $linksHtml = '<a href="/about" style="color:rgba(255,255,255,.8);text-decoration:none;font-size:.9rem;font-weight:500;">About</a>';
        }
        $cta = $c['cta_text'] ?? '';
        $ctaHtml = $cta
            ? "<a href=\"" . htmlspecialchars($c['cta_url'] ?? '#', ENT_QUOTES) . "\" style=\"background:{$accent};color:white;padding:.45rem 1.1rem;border-radius:7px;font-size:.88rem;font-weight:700;text-decoration:none;\">" . htmlspecialchars($cta) . "</a>"
            : '';

        return <<<HTML
<nav id="{$id}" style="background:{$bg};padding:0 2rem;height:62px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;box-shadow:0 2px 12px rgba(0,0,0,.18);">
  <div style="display:flex;align-items:center;gap:.75rem;">{$logoHtml}</div>
  <div style="display:flex;align-items:center;gap:.5rem;">{$linksHtml}</div>
  <div>{$ctaHtml}</div>
</nav>
HTML;
    }

    private function builderHeading(array $c, string $id): string
    {
        $text  = htmlspecialchars($c['text'] ?? 'Section Heading', ENT_QUOTES);
        $level = in_array($c['level'] ?? 'h2', ['h1','h2','h3','h4','h5','h6']) ? ($c['level'] ?? 'h2') : 'h2';
        $align = htmlspecialchars($c['align'] ?? 'left', ENT_QUOTES);
        $color = htmlspecialchars($c['color'] ?? '#111827', ENT_QUOTES);
        $size  = (int)($c['size'] ?? 32);
        return "<{$level} id=\"{$id}\" style=\"text-align:{$align};color:{$color};font-size:{$size}px;font-weight:800;line-height:1.2;letter-spacing:-.02em;padding:.5rem 0;\">{$text}</{$level}>";
    }

    private function builderRichtext(array $c, string $id): string
    {
        $html = $c['content'] ?? '';
        $pad  = (int)($c['pad'] ?? 28);
        return "<div id=\"{$id}\" class=\"cms-richtext\" style=\"padding:{$pad}px 0;line-height:1.75;max-width:860px;\">{$html}</div>";
    }

    private function builderCards(array $c, string $id): string
    {
        $heading = htmlspecialchars($c['heading'] ?? 'Our Features', ENT_QUOTES);
        $cols    = max(1, min(6, (int)($c['cols'] ?? 3)));
        $bg      = htmlspecialchars($c['bg'] ?? '#f9fafb', ENT_QUOTES);
        $items   = $c['items'] ?? [];

        $cardsHtml = '';
        foreach ($items as $item) {
            $icon  = htmlspecialchars($item['icon']  ?? '', ENT_QUOTES);
            $title = htmlspecialchars($item['title'] ?? '', ENT_QUOTES);
            $desc  = htmlspecialchars($item['desc']  ?? '', ENT_QUOTES);
            $cardsHtml .= <<<CARD
<div style="background:white;border:1.5px solid #e5e7eb;border-radius:14px;padding:1.5rem;display:flex;flex-direction:column;gap:.5rem;box-shadow:0 2px 10px rgba(0,0,0,.05);">
  <div style="font-size:1.75rem;width:48px;height:48px;background:#ede9fe;border-radius:10px;display:flex;align-items:center;justify-content:center;">{$icon}</div>
  <div style="font-size:1rem;font-weight:700;color:#111827;">{$title}</div>
  <div style="font-size:.875rem;color:#6b7280;line-height:1.6;">{$desc}</div>
</div>
CARD;
        }

        return <<<HTML
<div id="{$id}" style="background:{$bg};padding:3.5rem 2rem;">
  <h2 style="text-align:center;font-size:1.75rem;font-weight:800;color:#111827;margin-bottom:2rem;letter-spacing:-.02em;">{$heading}</h2>
  <div style="max-width:1100px;margin:0 auto;display:grid;grid-template-columns:repeat({$cols},1fr);gap:1.25rem;">
    {$cardsHtml}
  </div>
</div>
HTML;
    }

    private function builderTestimonials(array $c, string $id): string
    {
        $heading = htmlspecialchars($c['heading'] ?? 'What Students Say', ENT_QUOTES);
        $cols    = max(1, min(4, (int)($c['cols'] ?? 2)));
        $bg      = htmlspecialchars($c['bg'] ?? '#ffffff', ENT_QUOTES);
        $items   = $c['items'] ?? [];

        $cards = '';
        foreach ($items as $item) {
            $quote = htmlspecialchars($item['quote'] ?? '', ENT_QUOTES);
            $name  = htmlspecialchars($item['name']  ?? '', ENT_QUOTES);
            $role  = htmlspecialchars($item['role']  ?? '', ENT_QUOTES);
            $cards .= <<<CARD
<div style="background:white;border:1.5px solid #e5e7eb;border-radius:14px;padding:1.5rem;">
  <p style="color:#374151;font-size:.9375rem;line-height:1.7;margin-bottom:1rem;font-style:italic;">"{$quote}"</p>
  <div style="font-weight:700;color:#111827;font-size:.875rem;">{$name}</div>
  <div style="color:#6366f1;font-size:.8rem;font-weight:600;">{$role}</div>
</div>
CARD;
        }

        return <<<HTML
<div id="{$id}" style="background:{$bg};padding:3.5rem 2rem;">
  <h2 style="text-align:center;font-size:1.75rem;font-weight:800;color:#111827;margin-bottom:2rem;letter-spacing:-.02em;">{$heading}</h2>
  <div style="max-width:1100px;margin:0 auto;display:grid;grid-template-columns:repeat({$cols},1fr);gap:1.5rem;">{$cards}</div>
</div>
HTML;
    }

    private function builderStats(array $c, string $id): string
    {
        $heading = htmlspecialchars($c['heading'] ?? 'By the Numbers', ENT_QUOTES);
        $bg      = htmlspecialchars($c['bg'] ?? '#f9fafb', ENT_QUOTES);
        $items   = $c['items'] ?? $c ?? [];

        $statsHtml = '';
        foreach ($items as $item) {
            if (!is_array($item)) continue;
            $val   = htmlspecialchars($item['value'] ?? $item['number'] ?? '0', ENT_QUOTES);
            $label = htmlspecialchars($item['label'] ?? '', ENT_QUOTES);
            $statsHtml .= "<div style=\"text-align:center;\"><div style=\"font-size:2.5rem;font-weight:900;color:#6366f1;letter-spacing:-.03em;\">{$val}</div><div style=\"font-size:.8rem;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-top:.25rem;\">{$label}</div></div>";
        }

        return <<<HTML
<div id="{$id}" style="background:{$bg};padding:3rem 2rem;">
  <h2 style="text-align:center;font-size:1.5rem;font-weight:800;color:#111827;margin-bottom:2rem;">{$heading}</h2>
  <div style="max-width:900px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:2.5rem;">{$statsHtml}</div>
</div>
HTML;
    }

    private function builderCta(array $c, string $id): string
    {
        $heading = htmlspecialchars($c['heading'] ?? 'Ready to Begin?', ENT_QUOTES);
        $sub     = htmlspecialchars($c['sub'] ?? '', ENT_QUOTES);
        $bg      = htmlspecialchars($c['bg'] ?? '#6366f1', ENT_QUOTES);
        $btn     = htmlspecialchars($c['btn'] ?? 'Apply Today', ENT_QUOTES);
        $btnurl  = htmlspecialchars($c['btnurl'] ?? '/apply', ENT_QUOTES);
        $btn2    = htmlspecialchars($c['btn2'] ?? '', ENT_QUOTES);
        $btn2url = htmlspecialchars($c['btn2url'] ?? '#', ENT_QUOTES);
        $subHtml = $sub ? "<p style=\"font-size:1rem;color:rgba(255,255,255,.85);margin-bottom:1.75rem;max-width:520px;margin-left:auto;margin-right:auto;\">{$sub}</p>" : '';
        $btn2Html = $btn2 ? "<a href=\"{$btn2url}\" style=\"padding:.75rem 2rem;border:2px solid rgba(255,255,255,.5);color:white;font-weight:700;font-size:.9rem;text-decoration:none;border-radius:50px;\">{$btn2}</a>" : '';

        return <<<HTML
<div id="{$id}" style="background:{$bg};padding:5rem 2rem;text-align:center;">
  <h2 style="font-size:clamp(1.5rem,4vw,2.5rem);font-weight:900;color:white;margin-bottom:.75rem;letter-spacing:-.025em;">{$heading}</h2>
  {$subHtml}
  <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
    <a href="{$btnurl}" style="background:white;color:{$bg};padding:.75rem 2rem;border-radius:50px;font-weight:700;font-size:.9rem;text-decoration:none;">{$btn}</a>
    {$btn2Html}
  </div>
</div>
HTML;
    }

    private function builderFooter(array $c, string $id): string
    {
        $siteName = htmlspecialchars($c['site_name'] ?? 'My College', ENT_QUOTES);
        $tagline  = htmlspecialchars($c['tagline'] ?? '', ENT_QUOTES);
        $bg       = htmlspecialchars($c['bg'] ?? '#0f172a', ENT_QUOTES);

        $links1 = $c['links1'] ?? [];
        $links2 = $c['links2'] ?? [];
        $l1Html = implode('', array_map(fn($l) => '<a href="#" style="color:rgba(255,255,255,.65);font-size:.85rem;text-decoration:none;display:block;margin-bottom:.4rem;">' . htmlspecialchars(is_array($l) ? ($l['label'] ?? '') : $l, ENT_QUOTES) . '</a>', $links1));
        $l2Html = implode('', array_map(fn($l) => '<a href="#" style="color:rgba(255,255,255,.65);font-size:.85rem;text-decoration:none;display:block;margin-bottom:.4rem;">' . htmlspecialchars(is_array($l) ? ($l['label'] ?? '') : $l, ENT_QUOTES) . '</a>', $links2));

        return <<<HTML
<footer id="{$id}" style="background:{$bg};padding:3.5rem 2rem 2rem;color:white;">
  <div style="max-width:1100px;margin:0 auto;display:grid;grid-template-columns:1fr auto auto;gap:3rem;align-items:start;">
    <div>
      <div style="font-size:1.25rem;font-weight:800;margin-bottom:.5rem;">{$siteName}</div>
      <div style="color:rgba(255,255,255,.55);font-size:.875rem;line-height:1.6;">{$tagline}</div>
    </div>
    <div>{$l1Html}</div>
    <div>{$l2Html}</div>
  </div>
  <div style="max-width:1100px;margin:2rem auto 0;border-top:1px solid rgba(255,255,255,.1);padding-top:1rem;font-size:.75rem;color:rgba(255,255,255,.35);">&copy; 2025 {$siteName}</div>
</footer>
HTML;
    }

    private function builderNotices(array $c, string $id): string
    {
        $heading = htmlspecialchars($c['heading'] ?? 'Notices', ENT_QUOTES);
        $items   = $c['items'] ?? [];

        $listHtml = '';
        foreach ($items as $item) {
            $date  = htmlspecialchars($item['date']  ?? '', ENT_QUOTES);
            $cat   = htmlspecialchars($item['cat']   ?? '', ENT_QUOTES);
            $title = htmlspecialchars($item['title'] ?? '', ENT_QUOTES);
            $body  = htmlspecialchars($item['body']  ?? '', ENT_QUOTES);
            $listHtml .= <<<ITEM
<li style="border-bottom:1px solid #e5e7eb;padding:1rem 0;display:flex;gap:1rem;align-items:flex-start;">
  <div style="min-width:60px;text-align:center;">
    <div style="font-size:.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;">{$cat}</div>
    <div style="font-size:.75rem;color:#94a3b8;">{$date}</div>
  </div>
  <div>
    <div style="font-weight:700;color:#111827;font-size:.9375rem;margin-bottom:.25rem;">{$title}</div>
    <div style="font-size:.85rem;color:#6b7280;line-height:1.5;">{$body}</div>
  </div>
</li>
ITEM;
        }

        return <<<HTML
<div id="{$id}" style="padding:3rem 2rem;">
  <h2 style="font-size:1.5rem;font-weight:800;color:#111827;margin-bottom:1.5rem;">{$heading}</h2>
  <ul style="list-style:none;padding:0;max-width:800px;">{$listHtml}</ul>
</div>
HTML;
    }

    private function builderPricing(array $c, string $id): string
    {
        $heading = htmlspecialchars($c['heading'] ?? 'Simple Pricing', ENT_QUOTES);
        $items   = $c['items'] ?? [];

        $cards = '';
        foreach ($items as $item) {
            $name     = htmlspecialchars($item['name']  ?? '', ENT_QUOTES);
            $price    = htmlspecialchars($item['price'] ?? '', ENT_QUOTES);
            $cta      = htmlspecialchars($item['cta']   ?? 'Get Started', ENT_QUOTES);
            $hi       = !empty($item['highlighted']);
            $border   = $hi ? 'border:2px solid #6366f1;box-shadow:0 8px 32px rgba(99,102,241,.18);' : 'border:1.5px solid #e5e7eb;';
            $featHtml = '';
            foreach ($item['features'] ?? [] as $f) {
                $featHtml .= '<li style="font-size:.85rem;color:#475569;padding:.25rem 0;">✓ ' . htmlspecialchars($f, ENT_QUOTES) . '</li>';
            }
            $cards .= <<<CARD
<div style="background:white;{$border}border-radius:16px;padding:2rem 1.5rem;text-align:center;">
  <div style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#64748b;margin-bottom:.5rem;">{$name}</div>
  <div style="font-size:2.4rem;font-weight:900;color:#111827;margin-bottom:1rem;">{$price}</div>
  <ul style="list-style:none;padding:0;margin-bottom:1.5rem;text-align:left;">{$featHtml}</ul>
  <a href="#" style="display:block;background:#6366f1;color:white;padding:.75rem;border-radius:9999px;font-weight:700;text-decoration:none;">{$cta}</a>
</div>
CARD;
        }

        return <<<HTML
<div id="{$id}" style="background:#f8fafc;padding:3.5rem 2rem;">
  <h2 style="text-align:center;font-size:1.75rem;font-weight:800;color:#111827;margin-bottom:2rem;">{$heading}</h2>
  <div style="max-width:900px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1.5rem;">{$cards}</div>
</div>
HTML;
    }

    private function builderContactForm(array $c, string $id): string
    {
        $heading = htmlspecialchars($c['heading'] ?? 'Get in Touch', ENT_QUOTES);
        $sub     = htmlspecialchars($c['subtext'] ?? '', ENT_QUOTES);
        $btn     = htmlspecialchars($c['btn_lbl'] ?? 'Send Message', ENT_QUOTES);
        $bg      = htmlspecialchars($c['bg'] ?? '#ffffff', ENT_QUOTES);
        $subHtml = $sub ? "<p style=\"color:#64748b;font-size:.9375rem;margin-bottom:1.5rem;\">{$sub}</p>" : '';

        return <<<HTML
<div id="{$id}" style="background:{$bg};padding:3.5rem 2rem;">
  <div style="max-width:560px;margin:0 auto;">
    <h2 style="font-size:1.75rem;font-weight:800;color:#111827;margin-bottom:.5rem;">{$heading}</h2>
    {$subHtml}
    <form style="display:flex;flex-direction:column;gap:1rem;">
      <input type="text" placeholder="Your Name" style="border:1.5px solid #e5e7eb;border-radius:8px;padding:.75rem 1rem;font-size:.95rem;" />
      <input type="email" placeholder="Email Address" style="border:1.5px solid #e5e7eb;border-radius:8px;padding:.75rem 1rem;font-size:.95rem;" />
      <textarea rows="5" placeholder="Your Message" style="border:1.5px solid #e5e7eb;border-radius:8px;padding:.75rem 1rem;font-size:.95rem;resize:vertical;"></textarea>
      <button type="submit" style="background:#6366f1;color:white;padding:.875rem;border-radius:9999px;font-weight:700;font-size:.95rem;cursor:pointer;border:none;">{$btn}</button>
    </form>
  </div>
</div>
HTML;
    }

    private function builderSection(array $c, string $id, array $tree, ?callable $recursiveRenderer): string
    {
        $bg  = htmlspecialchars($c['bg'] ?? '#ffffff', ENT_QUOTES);
        $pad = (int)($c['pad'] ?? 48);
        $inner = $recursiveRenderer ? $recursiveRenderer($tree, $id, 'main') : '';
        return "<div id=\"{$id}\" style=\"background:{$bg};padding:{$pad}px 2rem;\">{$inner}</div>";
    }

    private function builderLayout(string $type, array $c, string $id, array $tree, ?callable $recursiveRenderer): string
    {
        $e = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
        $bg   = $e($c['bg'] ?? '#ffffff');
        $pad  = (int)($c['pad'] ?? 32);
        
        if ($type === 'cols2') {
            $lw = (int)($c['lw'] ?? 50);
            $gap = (int)($c['gap'] ?? 24);
            $c1 = $recursiveRenderer ? $recursiveRenderer($tree, $id, 'col1') : '';
            $c2 = $recursiveRenderer ? $recursiveRenderer($tree, $id, 'col2') : '';
            return "<div id=\"{$id}\" style=\"background:{$bg};padding:{$pad}px 2rem;\"><div style=\"display:grid;grid-template-columns:{$lw}fr ".(100-$lw)."fr;gap:{$gap}px;\"><div>{$c1}</div><div>{$c2}</div></div></div>";
        }
        
        if ($type === 'cols3') {
            $gap = (int)($c['gap'] ?? 20);
            $c1 = $recursiveRenderer ? $recursiveRenderer($tree, $id, 'col1') : '';
            $c2 = $recursiveRenderer ? $recursiveRenderer($tree, $id, 'col2') : '';
            $c3 = $recursiveRenderer ? $recursiveRenderer($tree, $id, 'col3') : '';
            return "<div id=\"{$id}\" style=\"background:{$bg};padding:{$pad}px 2rem;\"><div style=\"display:grid;grid-template-columns:1fr 1fr 1fr;gap:{$gap}px;\"><div>{$c1}</div><div>{$c2}</div><div>{$c3}</div></div></div>";
        }

        if ($type === 'aside-left') {
            $aw = (int)($c['asideWidth'] ?? 25);
            $abg = $e($c['asideBg'] ?? '#f8fafc');
            $sidebar = $recursiveRenderer ? $recursiveRenderer($tree, $id, 'aside') : '';
            $main    = $recursiveRenderer ? $recursiveRenderer($tree, $id, 'main') : '';
            return "<div id=\"{$id}\" style=\"background:{$bg};display:grid;grid-template-columns:{$aw}fr ".(100-$aw)."fr;\"><div style=\"background:{$abg};border-right:1px solid #e5e7eb;padding:2rem\">{$sidebar}</div><div style=\"padding:2rem\">{$main}</div></div>";
        }

        if ($type === 'aside-right') {
            $aw = (int)($c['asideWidth'] ?? 25);
            $abg = $e($c['asideBg'] ?? '#f8fafc');
            $main    = $recursiveRenderer ? $recursiveRenderer($tree, $id, 'main') : '';
            $sidebar = $recursiveRenderer ? $recursiveRenderer($tree, $id, 'aside') : '';
            return "<div id=\"{$id}\" style=\"background:{$bg};display:grid;grid-template-columns:".(100-$aw)."fr {$aw}fr;\"><div style=\"padding:2rem\">{$main}</div><div style=\"background:{$abg};border-left:1px solid #e5e7eb;padding:2rem\">{$sidebar}</div></div>";
        }

        return "<div id=\"{$id}\" style=\"background:{$bg};padding:{$pad}px 2rem;\"></div>";
    }

    // ═══════════════════════════════════════════════════════════════
    // Plugin fallback (for actual plugins)
    // ═══════════════════════════════════════════════════════════════

    private function plugin(string $type, array $c, string $id): string
    {
        $dir  = base_path("plugins/{$type}");
        $html = file_exists("{$dir}/component.html")
            ? file_get_contents("{$dir}/component.html")
            : "<div style=\"padding:1rem;background:#fef2f2;border-radius:8px;color:#dc2626;\">Plugin <code>{$type}</code> not found.</div>";

        // SECURITY: sanitize plugin HTML to prevent stored XSS
        try {
            $html = \App\Services\HtmlSanitizer::sanitize($html);
        } catch (\Throwable) {
            // If sanitizer fails for any reason, continue with unsanitized HTML
            // rather than breaking the page — log in production
        }

        foreach ($c as $key => $val) {
            // Skip array values (items, images, links etc.) — they can't be serialised as a setting attribute
            if (is_array($val)) continue;
            $html = preg_replace(
                '/data-setting="' . preg_quote($key, '/') . '"[^>]*>[^<]*/',
                'data-setting="' . $key . '">' . htmlspecialchars((string)$val),
                $html
            );
        }
        return str_replace('{{instance}}', $id, $html);
    }

    // ── Load-more Gallery ─────────────────────────────────────────────

    private function loadMoreGallery(array $c, string $id): string
    {
        $heading    = htmlspecialchars($c['heading']      ?? 'Gallery',   ENT_QUOTES);
        $src        = preg_replace('/[^a-z0-9_-]/i', '', $c['data_source'] ?? 'gallery');
        $imgField   = htmlspecialchars($c['image_field']  ?? 'image',     ENT_QUOTES);
        $titleField = htmlspecialchars($c['title_field']  ?? 'title',     ENT_QUOTES);
        $initN      = (int) ($c['initial_load'] ?? 12);
        $moreN      = (int) ($c['load_more_n']  ?? 6);
        $cols       = (int) ($c['columns']       ?? 3);

        // Inline the gallery plugin JS/CSS directly so it works as a built-in
        $pluginDir  = base_path('plugins/gallery-page');
        $css        = file_exists("{$pluginDir}/component.css")
            ? '<style>' . file_get_contents("{$pluginDir}/component.css") . '</style>'
            : '';
        $jsSrc      = file_exists("{$pluginDir}/component.js")
            ? file_get_contents("{$pluginDir}/component.js")
            : '';

        $settings = json_encode([
            'heading'      => $c['heading']      ?? 'Gallery',
            'data_source'  => $src,
            'image_field'  => $c['image_field']  ?? 'image',
            'title_field'  => $c['title_field']  ?? 'title',
            'initial_load' => $initN,
            'load_more_n'  => $moreN,
            'columns'      => $cols,
        ], JSON_HEX_TAG | JSON_UNESCAPED_UNICODE);

        return <<<HTML
{$css}
<section class="glpg" id="gallery-page-{$id}">
  <div class="glpg__wrap">
    <div class="glpg__header"><h2 class="glpg__heading">{$heading}</h2></div>
    <div class="glpg__spinner-wrap" id="glpg-spinner-{$id}"><div class="glpg__spinner"></div></div>
    <div class="glpg__grid" id="glpg-grid-{$id}"></div>
    <p class="glpg__status" id="glpg-status-{$id}"></p>
    <div class="glpg__footer" id="glpg-footer-{$id}" style="display:none;">
      <button class="glpg__load-btn" id="glpg-load-btn-{$id}">
        <span class="glpg__btn-label">Load more</span>
        <span class="glpg__btn-spinner" style="display:none;"></span>
      </button>
    </div>
  </div>
  <div class="glpg__lightbox" id="glpg-lightbox-{$id}" aria-hidden="true">
    <div class="glpg__lb-overlay"></div>
    <div class="glpg__lb-box">
      <button class="glpg__lb-close" aria-label="Close">✕</button>
      <button class="glpg__lb-prev" aria-label="Previous">‹</button>
      <button class="glpg__lb-next" aria-label="Next">›</button>
      <img class="glpg__lb-img" src="" alt="" />
      <div class="glpg__lb-caption"></div>
    </div>
  </div>
</section>
<script>
(function(){
    var pluginElement  = document.getElementById('gallery-page-{$id}');
    var pluginSettings = {$settings};
    if (pluginElement) { {$jsSrc} }
})();
</script>
HTML;
    }

    // ── Notices / Paginated List ───────────────────────────────────────

    private function noticesComponent(array $c, string $id): string
    {
        $heading  = htmlspecialchars($c['heading']     ?? 'Notices',    ENT_QUOTES);
        $src      = preg_replace('/[^a-z0-9_-]/i', '', $c['data_source'] ?? 'notices');
        $baseSlug = preg_replace('/[^a-z0-9-]/i',  '', $c['base_slug']   ?? 'notices');
        $perPage  = (int) ($c['per_page'] ?? 10);

        $pluginDir = base_path('plugins/notices');
        $css       = file_exists("{$pluginDir}/component.css")
            ? '<style>' . file_get_contents("{$pluginDir}/component.css") . '</style>'
            : '';
        $jsSrc     = file_exists("{$pluginDir}/component.js")
            ? file_get_contents("{$pluginDir}/component.js")
            : '';

        $settings = json_encode(array_merge([
            'heading'        => $c['heading']        ?? 'Notices',
            'data_source'    => $src,
            'title_field'    => $c['title_field']    ?? 'title',
            'body_field'     => $c['body_field']     ?? 'body',
            'date_field'     => $c['date_field']     ?? 'created_at',
            'category_field' => $c['category_field'] ?? 'category',
            'base_slug'      => $baseSlug,
            'per_page'       => $perPage,
            'show_search'    => $c['show_search']    ?? 'true',
        ], isset($c['__current_page']) ? ['__current_page' => (int)$c['__current_page']] : []),
        JSON_HEX_TAG | JSON_UNESCAPED_UNICODE);

        return <<<HTML
{$css}
<section class="ntpg" id="notices-{$id}">
  <div class="ntpg__wrap">
    <div class="ntpg__header">
      <h2 class="ntpg__heading">{$heading}</h2>
      <div class="ntpg__search-bar" id="ntpg-search-wrap-{$id}">
        <input type="search" class="ntpg__search-input" id="ntpg-search-{$id}" placeholder="Search notices…" />
      </div>
    </div>
    <div class="ntpg__filters" id="ntpg-filters-{$id}"></div>
    <div class="ntpg__spinner-wrap" id="ntpg-spinner-{$id}"><div class="ntpg__spinner"></div></div>
    <ul class="ntpg__list" id="ntpg-list-{$id}" role="list"></ul>
    <p class="ntpg__empty" id="ntpg-empty-{$id}" style="display:none;"></p>
    <nav class="ntpg__pagination" id="ntpg-pagination-{$id}" aria-label="Page navigation"></nav>
  </div>
</section>
<script>
(function(){
    var pluginElement  = document.getElementById('notices-{$id}');
    var pluginSettings = {$settings};
    if (pluginElement) { {$jsSrc} }
})();
</script>
HTML;
    }

    // ── Animated Counter ──────────────────────────────────────────────

    private function counter(array $c, string $id): string
    {
        $items  = $c['items'] ?? [
            ['number' => '15000', 'suffix' => '+', 'label' => 'Students', 'icon' => '🎓'],
            ['number' => '500',   'suffix' => '+', 'label' => 'Faculty',  'icon' => '👨‍🏫'],
            ['number' => '80',    'suffix' => '+', 'label' => 'Programs', 'icon' => '📚'],
            ['number' => '25',    'suffix' => '',  'label' => 'Years',    'icon' => '🏛️'],
        ];
        $bg     = htmlspecialchars($c['bg_color']   ?? '#1e3a5f', ENT_QUOTES);
        $tc     = htmlspecialchars($c['text_color'] ?? '#ffffff', ENT_QUOTES);
        $accent = htmlspecialchars($c['accent']     ?? '#818cf8', ENT_QUOTES);
        $dur    = (int) ($c['duration_ms'] ?? 2000);

        $cards = '';
        foreach ($items as $i => $item) {
            $num    = (int) preg_replace('/[^0-9]/', '', $item['number'] ?? '0');
            $suffix = htmlspecialchars($item['suffix'] ?? '', ENT_QUOTES);
            $label  = htmlspecialchars($item['label']  ?? '', ENT_QUOTES);
            $icon   = htmlspecialchars($item['icon']   ?? '', ENT_QUOTES);
            $cards .= <<<CARD
<div class="ctr-card">
  <div class="ctr-icon">{$icon}</div>
  <div class="ctr-num" data-target="{$num}" data-suffix="{$suffix}">0{$suffix}</div>
  <div class="ctr-label">{$label}</div>
</div>
CARD;
        }

        return <<<HTML
<div class="ctr-wrap" id="ctr-{$id}" style="background:{$bg};color:{$tc};">
  <div class="ctr-grid">{$cards}</div>
</div>
<style>
.ctr-wrap{padding:3.5rem 1.5rem;text-align:center;}
.ctr-grid{max-width:900px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:2rem;}
.ctr-card{display:flex;flex-direction:column;align-items:center;gap:.4rem;}
.ctr-icon{font-size:2.2rem;line-height:1;}
.ctr-num{font-size:2.8rem;font-weight:900;color:{$accent};letter-spacing:-0.03em;line-height:1;}
.ctr-label{font-size:.88rem;opacity:.75;font-weight:500;letter-spacing:.04em;text-transform:uppercase;}
</style>
<script>
(function(){
  var root=document.getElementById('ctr-{$id}');
  if(!root)return;
  var dur={$dur};
  function animateNum(el){
    var target=parseInt(el.dataset.target)||0;
    var suffix=el.dataset.suffix||'';
    var start=performance.now();
    function step(now){
      var p=Math.min((now-start)/dur,1);
      var ease=p<.5?2*p*p:-1+(4-2*p)*p;
      el.textContent=Math.floor(ease*target).toLocaleString()+suffix;
      if(p<1)requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }
  var observer=new IntersectionObserver(function(entries){
    entries.forEach(function(e){if(e.isIntersecting){root.querySelectorAll('.ctr-num').forEach(animateNum);observer.disconnect();}});
  },{threshold:0.3});
  observer.observe(root);
})();
</script>
HTML;
    }

    // ── Timeline ──────────────────────────────────────────────────────

    private function timeline(array $c, string $id): string
    {
        $heading = htmlspecialchars($c['heading'] ?? 'Our Journey', ENT_QUOTES);
        $items   = $c['items'] ?? [
            ['year' => '1998', 'title' => 'Founded',         'text' => 'College established with a vision of excellence.'],
            ['year' => '2005', 'title' => 'First Expansion',  'text' => 'New engineering and science faculties opened.'],
            ['year' => '2015', 'title' => 'Global Rankings',  'text' => 'Ranked among top 50 colleges nationally.'],
            ['year' => '2024', 'title' => 'Innovation Hub',   'text' => 'State-of-the-art research centre inaugurated.'],
        ];
        $accent = htmlspecialchars($c['accent_color'] ?? '#6366f1', ENT_QUOTES);

        $entries = '';
        foreach ($items as $i => $item) {
            $year  = htmlspecialchars($item['year']  ?? '', ENT_QUOTES);
            $title = htmlspecialchars($item['title'] ?? '', ENT_QUOTES);
            $text  = htmlspecialchars($item['text']  ?? '', ENT_QUOTES);
            $side  = $i % 2 === 0 ? 'left' : 'right';
            $entries .= <<<ENTRY
<div class="tl-item tl-{$side}">
  <div class="tl-dot"></div>
  <div class="tl-card">
    <div class="tl-year">{$year}</div>
    <h3 class="tl-title">{$title}</h3>
    <p class="tl-text">{$text}</p>
  </div>
</div>
ENTRY;
        }

        return <<<HTML
<div class="tl-wrap" id="tl-{$id}" style="--tl-accent:{$accent};">
  <h2 class="tl-heading">{$heading}</h2>
  <div class="tl-line"></div>
  <div class="tl-entries">{$entries}</div>
</div>
<style>
.tl-wrap{padding:3rem 1.5rem;position:relative;max-width:900px;margin:0 auto;}
.tl-heading{text-align:center;font-size:1.75rem;font-weight:800;color:#1e293b;margin-bottom:2.5rem;letter-spacing:-0.02em;}
.tl-line{position:absolute;left:50%;top:5rem;bottom:2rem;width:2px;background:var(--tl-accent);opacity:.25;transform:translateX(-50%);}
.tl-entries{display:flex;flex-direction:column;gap:2rem;}
.tl-item{display:flex;align-items:flex-start;gap:1.5rem;position:relative;}
.tl-left{flex-direction:row;}
.tl-right{flex-direction:row-reverse;}
.tl-dot{width:14px;height:14px;background:var(--tl-accent);border-radius:50%;flex-shrink:0;margin-top:.4rem;box-shadow:0 0 0 4px rgba(99,102,241,.15);position:relative;z-index:1;}
.tl-card{background:white;border:1.5px solid #e2e8f0;border-radius:12px;padding:1.25rem 1.5rem;flex:1;box-shadow:0 2px 12px rgba(0,0,0,.05);transition:box-shadow .2s;}
.tl-card:hover{box-shadow:0 8px 28px rgba(99,102,241,.12);}
.tl-year{font-size:.72rem;font-weight:700;color:var(--tl-accent);text-transform:uppercase;letter-spacing:.08em;margin-bottom:.3rem;}
.tl-title{font-size:1rem;font-weight:700;color:#1e293b;margin-bottom:.4rem;}
.tl-text{font-size:.85rem;color:#64748b;line-height:1.6;}
@media(max-width:600px){.tl-line{display:none;}.tl-item{flex-direction:row!important;}.tl-right{flex-direction:row!important;}}
</style>
HTML;
    }

    // ── Team Grid ─────────────────────────────────────────────────────

    private function teamGrid(array $c, string $id): string
    {
        $heading = htmlspecialchars($c['heading'] ?? 'Our Team', ENT_QUOTES);
        $members = $c['members'] ?? [
            ['name' => 'Dr. Sarah Ahmed',  'role' => 'Principal',          'avatar' => '', 'bio' => ''],
            ['name' => 'Prof. John Smith', 'role' => 'Head of Engineering', 'avatar' => '', 'bio' => ''],
            ['name' => 'Dr. Priya Kumar',  'role' => 'Head of Science',     'avatar' => '', 'bio' => ''],
        ];
        $cols   = (int) ($c['columns'] ?? 3);
        $accent = htmlspecialchars($c['accent_color'] ?? '#6366f1', ENT_QUOTES);

        $cards = '';
        foreach ($members as $m) {
            $name   = htmlspecialchars($m['name']   ?? '', ENT_QUOTES);
            $role   = htmlspecialchars($m['role']   ?? '', ENT_QUOTES);
            $bio    = htmlspecialchars($m['bio']    ?? '', ENT_QUOTES);
            $avatar = $m['avatar'] ?? '';
            $img    = $avatar
                ? "<img src=\"{$avatar}\" alt=\"{$name}\" class=\"tgrd-avatar\" />"
                : "<div class=\"tgrd-avatar tgrd-initials\">" . htmlspecialchars(mb_substr($m['name'] ?? '?', 0, 1), ENT_QUOTES) . "</div>";
            $bioPara = $bio ? '<p class="tgrd-bio">' . $bio . '</p>' : '';
            $cards .= <<<CARD
<div class="tgrd-card">
  {$img}
  <div class="tgrd-name">{$name}</div>
  <div class="tgrd-role">{$role}</div>
  {$bioPara}
</div>
CARD;
        }

        return <<<HTML
<div class="tgrd-wrap" id="tgrd-{$id}" style="--tgrd-accent:{$accent};--tgrd-cols:{$cols};">
  <h2 class="tgrd-heading">{$heading}</h2>
  <div class="tgrd-grid">{$cards}</div>
</div>
<style>
.tgrd-wrap{padding:3rem 1.5rem;}
.tgrd-heading{text-align:center;font-size:1.75rem;font-weight:800;color:#1e293b;margin-bottom:2rem;letter-spacing:-0.02em;}
.tgrd-grid{max-width:1100px;margin:0 auto;display:grid;grid-template-columns:repeat(var(--tgrd-cols,3),1fr);gap:1.75rem;}
@media(max-width:768px){.tgrd-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:480px){.tgrd-grid{grid-template-columns:1fr;}}
.tgrd-card{background:white;border:1.5px solid #e2e8f0;border-radius:16px;padding:2rem 1.5rem;text-align:center;transition:box-shadow .22s,transform .22s;}
.tgrd-card:hover{box-shadow:0 12px 32px rgba(99,102,241,.12);transform:translateY(-4px);}
.tgrd-avatar{width:80px;height:80px;border-radius:50%;object-fit:cover;margin:0 auto 1rem;display:block;border:3px solid var(--tgrd-accent);}
.tgrd-initials{background:var(--tgrd-accent);color:white;font-size:2rem;font-weight:700;display:flex;align-items:center;justify-content:center;}
.tgrd-name{font-size:1rem;font-weight:700;color:#1e293b;margin-bottom:.25rem;}
.tgrd-role{font-size:.78rem;color:var(--tgrd-accent);font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem;}
.tgrd-bio{font-size:.82rem;color:#64748b;line-height:1.55;margin:0;}
</style>
HTML;
    }

    // ── Pricing Table ─────────────────────────────────────────────────

    private function priceTable(array $c, string $id): string
    {
        $heading = htmlspecialchars($c['heading'] ?? 'Pricing', ENT_QUOTES);
        $plans   = $c['plans'] ?? [
            ['name' => 'Basic',       'price' => '₹15,000', 'period' => '/year', 'features' => ['Full library access','Online resources','Student ID card'],    'highlight' => false, 'btn_text' => 'Apply Now', 'btn_url' => '/admissions'],
            ['name' => 'Standard',    'price' => '₹25,000', 'period' => '/year', 'features' => ['Everything in Basic','Lab access','Hostel discount'],           'highlight' => true,  'btn_text' => 'Apply Now', 'btn_url' => '/admissions'],
            ['name' => 'Full Scholarship', 'price' => '₹0', 'period' => '/year', 'features' => ['Merit-based','All facilities','Stipend eligible'],              'highlight' => false, 'btn_text' => 'Apply Now', 'btn_url' => '/admissions'],
        ];
        $accent = htmlspecialchars($c['accent_color'] ?? '#6366f1', ENT_QUOTES);

        $cards = '';
        foreach ($plans as $plan) {
            $name      = htmlspecialchars($plan['name']     ?? '', ENT_QUOTES);
            $price     = htmlspecialchars($plan['price']    ?? '', ENT_QUOTES);
            $period    = htmlspecialchars($plan['period']   ?? '', ENT_QUOTES);
            $btnText   = htmlspecialchars($plan['btn_text'] ?? 'Apply', ENT_QUOTES);
            $btnUrl    = htmlspecialchars($plan['btn_url']  ?? '#', ENT_QUOTES);
            $highlight = ! empty($plan['highlight']);
            $cls       = $highlight ? ' ptbl-highlight' : '';
            $features  = implode('', array_map(fn($f) =>
                '<li class="ptbl-feat"><span>✓</span> ' . htmlspecialchars($f, ENT_QUOTES) . '</li>',
                $plan['features'] ?? []
            ));
            $badge = $highlight ? '<div class="ptbl-badge">Most Popular</div>' : '';
            $cards .= <<<CARD
<div class="ptbl-card{$cls}">
  {$badge}
  <div class="ptbl-name">{$name}</div>
  <div class="ptbl-price">{$price}<span class="ptbl-period">{$period}</span></div>
  <ul class="ptbl-feats">{$features}</ul>
  <a href="{$btnUrl}" class="ptbl-btn">{$btnText}</a>
</div>
CARD;
        }

        return <<<HTML
<div class="ptbl-wrap" id="ptbl-{$id}" style="--ptbl-accent:{$accent};">
  <h2 class="ptbl-heading">{$heading}</h2>
  <div class="ptbl-grid">{$cards}</div>
</div>
<style>
.ptbl-wrap{padding:3rem 1.5rem;background:#f8fafc;}
.ptbl-heading{text-align:center;font-size:1.75rem;font-weight:800;color:#1e293b;margin-bottom:2rem;letter-spacing:-0.02em;}
.ptbl-grid{max-width:1000px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1.5rem;align-items:start;}
.ptbl-card{background:white;border:1.5px solid #e2e8f0;border-radius:16px;padding:2rem 1.5rem;text-align:center;position:relative;}
.ptbl-highlight{border-color:var(--ptbl-accent);box-shadow:0 8px 32px rgba(99,102,241,.15);transform:scale(1.03);}
.ptbl-badge{position:absolute;top:-1px;left:50%;transform:translateX(-50%);background:var(--ptbl-accent);color:white;font-size:.65rem;font-weight:700;padding:.25rem .9rem;border-radius:0 0 8px 8px;letter-spacing:.06em;text-transform:uppercase;white-space:nowrap;}
.ptbl-name{font-size:.85rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#64748b;margin-bottom:.5rem;}
.ptbl-price{font-size:2.4rem;font-weight:900;color:#1e293b;line-height:1;}
.ptbl-period{font-size:.85rem;font-weight:500;color:#94a3b8;margin-left:.15rem;}
.ptbl-feats{list-style:none;margin:1.25rem 0;padding:0;text-align:left;display:flex;flex-direction:column;gap:.5rem;}
.ptbl-feat{font-size:.85rem;color:#475569;display:flex;gap:.5rem;align-items:flex-start;}
.ptbl-feat span{color:var(--ptbl-accent);font-weight:700;flex-shrink:0;}
.ptbl-btn{display:block;margin-top:1.5rem;background:var(--ptbl-accent);color:white;padding:.75rem;border-radius:9999px;font-weight:700;font-size:.88rem;text-decoration:none;transition:opacity .15s;}
.ptbl-btn:hover{opacity:.85;}
</style>
HTML;
    }

    // ── CTA Banner ───────────────────────────────────────────────────

    private function ctaBanner(array $c, string $id): string
    {
        $headline = htmlspecialchars($c['headline']   ?? 'Ready to get started?',      ENT_QUOTES);
        $sub      = htmlspecialchars($c['subtext']    ?? 'Join thousands of students.', ENT_QUOTES);
        $btn1Text = htmlspecialchars($c['btn1_text']  ?? 'Apply Now',                  ENT_QUOTES);
        $btn1Url  = htmlspecialchars($c['btn1_url']   ?? '/admissions',                ENT_QUOTES);
        $btn2Text = htmlspecialchars($c['btn2_text']  ?? 'Learn More',                 ENT_QUOTES);
        $btn2Url  = htmlspecialchars($c['btn2_url']   ?? '#',                          ENT_QUOTES);
        $bg       = htmlspecialchars($c['bg_color']   ?? '#1e3a5f',                    ENT_QUOTES);
        $tc       = htmlspecialchars($c['text_color'] ?? '#ffffff',                    ENT_QUOTES);
        $accent   = htmlspecialchars($c['accent']     ?? '#6366f1',                    ENT_QUOTES);
        $bgImg    = $c['bg_image'] ?? '';
        $bgStyle  = $bgImg
            ? "background:linear-gradient(rgba(0,0,0,.5),rgba(0,0,0,.5)),url('{$bgImg}') center/cover no-repeat;background-color:{$bg};"
            : "background:{$bg};";

        $btn2Html = $btn2Text
            ? "<a href=\"{$btn2Url}\" style=\"display:inline-flex;align-items:center;padding:.875rem 2rem;border-radius:9999px;font-weight:700;font-size:1rem;text-decoration:none;color:white;border:2px solid rgba(255,255,255,.45);transition:all .18s;\">{$btn2Text}</a>"
            : '';

        $subPara = $sub ? '<p class="ctab-sub">' . $sub . '</p>' : '';
        return <<<HTML
<div class="ctab-wrap" id="ctab-{$id}" style="{$bgStyle}color:{$tc};">
  <div class="ctab-inner">
    <h2 class="ctab-headline">{$headline}</h2>
    {$subPara}
    <div class="ctab-btns">
      <a href="{$btn1Url}" style="display:inline-flex;align-items:center;padding:.875rem 2.25rem;border-radius:9999px;font-weight:700;font-size:1rem;text-decoration:none;background:{$accent};color:white;box-shadow:0 6px 20px rgba(0,0,0,.25);transition:all .18s;">{$btn1Text}</a>
      {$btn2Html}
    </div>
  </div>
</div>
<style>
.ctab-wrap{padding:5rem 1.5rem;text-align:center;}
.ctab-inner{max-width:700px;margin:0 auto;}
.ctab-headline{font-size:2.25rem;font-weight:900;line-height:1.15;margin-bottom:.75rem;letter-spacing:-0.02em;}
.ctab-sub{font-size:1.05rem;opacity:.8;margin-bottom:2rem;line-height:1.6;}
.ctab-btns{display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;}
.ctab-btns a:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(0,0,0,.3);}
</style>
HTML;
    }

    // ── Map Embed ─────────────────────────────────────────────────────

    private function mapEmbed(array $c, string $id): string
    {
        $src    = $c['embed_url'] ?? '';
        $height = htmlspecialchars($c['height'] ?? '400px', ENT_QUOTES);
        $radius = htmlspecialchars($c['border_radius'] ?? '12px', ENT_QUOTES);
        $label  = htmlspecialchars($c['label'] ?? '', ENT_QUOTES);

        if (! $src) {
            return <<<HTML
<div id="map-{$id}" style="height:{$height};border-radius:{$radius};background:#e2e8f0;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:.9rem;flex-direction:column;gap:.5rem;">
  <div style="font-size:2rem;">🗺️</div>
  <div>Paste a Google Maps embed URL in the Content panel</div>
</div>
HTML;
        }

        // Sanitise: only allow Google Maps embed URLs
        $safe = preg_match('#^https://www\.google\.com/maps/embed#', $src) ? $src : '';
        if (! $safe) {
            return "<div id=\"map-{$id}\" style=\"padding:1rem;background:#fef2f2;border-radius:8px;color:#dc2626;font-size:.85rem;\">⚠️ Only Google Maps embed URLs are accepted for security.</div>";
        }

        $labelHtml = $label ? "<p style=\"text-align:center;font-size:.82rem;color:#64748b;margin-top:.75rem;\">{$label}</p>" : '';

        return <<<HTML
<div id="map-{$id}">
  <iframe
    src="{$safe}"
    width="100%"
    height="{$height}"
    style="border:0;border-radius:{$radius};display:block;"
    allowfullscreen=""
    loading="lazy"
    referrerpolicy="no-referrer-when-downgrade"
    title="Map"></iframe>
  {$labelHtml}
</div>
HTML;
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public static function cellClasses(array $cell): string
    {
        $classes = ['cms-cell', 'span-' . ($cell['span'] ?? 12)];
        if (! empty($cell['span_md'])) $classes[] = 'md-span-' . $cell['span_md'];
        if (! empty($cell['span_sm'])) $classes[] = 'sm-span-' . $cell['span_sm'];
        if (! empty($cell['align']))   $classes[] = 'align-' . $cell['align'];
        if (! empty($cell['valign']))  $classes[] = 'valign-' . $cell['valign'];
        if (! empty($cell['class']))   $classes[] = $cell['class'];
        return implode(' ', $classes);
    }

    public static function rowClasses(array $row): string
    {
        $classes = ['cms-row', $row['width'] ?? 'boxed', 'gap-' . ($row['gap'] ?? 'md'), 'pad-' . ($row['padding'] ?? 'none')];
        if (! empty($row['class'])) $classes[] = $row['class'];
        return implode(' ', $classes);
    }
}
