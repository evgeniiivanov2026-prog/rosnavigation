<?php
declare(strict_types=1);

function dawn_scene(): string
{
    return <<<'SVG'
<svg class="scene__svg" viewBox="0 0 720 640" aria-hidden="true" focusable="false">
  <defs>
    <linearGradient id="sky" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#121a2b"/>
      <stop offset=".42" stop-color="#3a2a3c"/>
      <stop offset=".72" stop-color="#e36a2c"/>
      <stop offset="1" stop-color="#ffc36b"/>
    </linearGradient>
    <linearGradient id="hill" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#2a211c"/>
      <stop offset="1" stop-color="#120e0c"/>
    </linearGradient>
    <linearGradient id="road" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#3a342e"/>
      <stop offset="1" stop-color="#16130f"/>
    </linearGradient>
  </defs>
  <rect width="720" height="640" fill="url(#sky)"/>
  <circle cx="470" cy="430" r="74" fill="#ffd27a" opacity=".95"/>
  <circle cx="470" cy="430" r="120" fill="#ffb15a" opacity=".25"/>
  <path d="M0 470c80-70 140-78 230-30 70 36 120 10 190-28 80-44 150-20 300 48v180H0z" fill="url(#hill)"/>
  <path d="M0 520c120-40 180-16 280 8 90 22 150-8 250-36 70-20 130-8 190 20v128H0z" fill="#1a140f"/>
  <path d="M360 468 250 640h220z" fill="url(#road)"/>
  <path class="scene__dashes" d="M360 490v150" stroke="#f2f5f8" stroke-width="4" stroke-linecap="round" stroke-dasharray="10 16" fill="none" opacity=".8"/>
  <g transform="translate(248 392)">
    <rect x="46" y="18" width="132" height="86" rx="4" fill="#1b140f" stroke="#ffb15a" stroke-width="2"/>
    <path d="M112 18v86" stroke="#3a3128" stroke-width="2"/>
    <path d="M58 48h108M58 72h108" stroke="#3a3128" stroke-width="2"/>
    <rect x="58" y="92" width="108" height="8" fill="#ff7a1a"/>
    <rect x="62" y="104" width="18" height="8" rx="1" fill="#ff4d3a"/>
    <rect x="144" y="104" width="18" height="8" rx="1" fill="#ff4d3a"/>
    <rect x="38" y="112" width="148" height="10" rx="2" fill="#0e0c0a"/>
    <g fill="#0a0908" stroke="#c8b8a2" stroke-width="3">
      <circle cx="78" cy="132" r="16"/>
      <circle cx="108" cy="132" r="16"/>
      <circle cx="150" cy="132" r="16"/>
    </g>
    <g fill="#2a241c">
      <circle cx="78" cy="132" r="5"/>
      <circle cx="108" cy="132" r="5"/>
      <circle cx="150" cy="132" r="5"/>
    </g>
  </g>
</svg>
SVG;
}
