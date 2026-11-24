const l=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M3 12h4l2.2-6 4.1 12 2.2-6H21" />
</svg>
`,v=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M10 6h10M4 12h16M8 18h12" />
</svg>
`,c=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<rect x="4" y="4" width="5" height="5" rx="1" /><rect x="15" y="4" width="5" height="5" rx="1" />
        <rect x="4" y="15" width="5" height="5" rx="1" /><rect x="15" y="15" width="5" height="5" rx="1" />
</svg>
`,g=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<rect x="3" y="4" width="18" height="4" rx="1" /><path d="M5 8v11a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8M10 12h4" />
</svg>
`,w=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M19 12H5M11 18l-6-6 6-6" />
</svg>
`,h=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M5 12h14M13 6l6 6-6 6" />
</svg>
`,_=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8h16m-4-4 4 4-4 4M20 16H4m4-4-4 4 4 4"/></svg>
`,d=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<circle cx="12" cy="8" r="5" /><path d="m8.5 12-1 9 4.5-2.5 4.5 2.5-1-9" />
</svg>
`,a=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="m3 9 9-6 9 6M5 10v8M10 10v8M14 10v8M19 10v8M3 21h18M2 18h20" />
</svg>
`,k=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" /><path d="M10 21h4" />
</svg>
`,p=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M3 5.5A7 7 0 0 1 12 7v13a7 7 0 0 0-9-1.5v-13ZM21 5.5A7 7 0 0 0 12 7v13a7 7 0 0 1 9-1.5v-13Z" />
</svg>
`,u=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M6 3h12v18l-6-4-6 4V3Z" />
</svg>
`,x=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="m21 8-9 5-9-5 9-5 9 5Z" /><path d="m3 8 9 5v9l-9-5V8ZM21 8l-9 5v9l9-5V8Z" />
</svg>
`,b=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<circle cx="11" cy="12" r="7" /><path d="m16 7-5 5M18 5l3-2-2 4M16 17l4 4" />
</svg>
`,M=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M8 9c.5-2 2-3 4.2-3 2.5 0 3.8 1.2 3.8 3.5V16c0 1 .3 1.7 1 2M16 12c-4-.2-7 .5-7 3 0 3.2 4.4 3.6 7 0" /><path d="M5 20c4 2 9 2.2 14-.5M17.5 18.5 20 19l-1 2" />
</svg>
`,j=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2 2.2 5.5 3.7 19 12 22l8.3-3 1.5-13.5L12 2Zm0 4.1 5.4 11.8h-2.5l-1.1-2.7h-3.7L9 17.9H6.6L12 6.1Zm0 3.9-1.1 3h2.2L12 10Z"/></svg>
`,m=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M4 2.5h16l-1.45 17L12 22l-6.55-2.5L4 2.5Zm3.25 3 .25 3h9.2l-.3 3.15H8l.25 2.9 3.75 1.15 3.7-1.15.2-2h-3.1l-.08.75-1 .3-1-.3-.05-.65H8.2l.3 2.55 3.5 1.1 4-1.25.8-8.85H7.25Z"/></svg>
`,f=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M4 10h3V7h3v3h3V7h3v3h3v3h2.5c-.5 3.4-3 5.5-7.5 5.5H8c-3.4 0-5.5-2.1-5.5-5.5H4v-3Zm0-6h3v2H4V4Zm3 0h3v2H7V4Zm3 0h3v2h-3V4Z"/></svg>
`,C=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<circle cx="12" cy="12" r="10" /><path d="M8 20c6-2 10-6 12-12M4 12c4 0 8-1 12-4M6 4c2 4 4 8 4 14" />
</svg>
`,B=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M14 21v-8h3l.5-4H14V7c0-1.2.4-2 2-2h2V2.4A25 25 0 0 0 15 2c-3 0-5 1.8-5 5v2H7v4h3v8" />
</svg>
`,y=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M15 21v-3.5c.1-1-.3-1.8-.8-2.3 2.7-.3 5.5-1.3 5.5-6A4.7 4.7 0 0 0 18.5 6 4.4 4.4 0 0 0 18.4 3S17.5 2.7 15 4.2a11 11 0 0 0-6 0C6.5 2.7 5.6 3 5.6 3A4.4 4.4 0 0 0 5.5 6a4.7 4.7 0 0 0-1.2 3.2c0 4.7 2.8 5.7 5.5 6-.4.4-.7 1-.8 1.8-2.2 1-2.7-1-2.7-1-.4-1.1-1.1-1.4-1.1-1.4-.9-.6.1-.6.1-.6 1 .1 1.6 1 1.6 1 .9 1.6 2.3 1.1 2.9.8V21" />
</svg>
`,Z=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M4 2.5h16l-1.45 17L12 22l-6.55-2.5L4 2.5Zm3.15 3 .25 3h9.2l-.3 3.15H7.7l.65 6.3 3.65 1.15 3.7-1.15.45-4.25h-3.1l-.2 1.85-1 .3-1-.3-.15-1.2H7.6l.4 3.1L12 18.6l4-1.25.8-8.85H7.7l-.2-3Z"/></svg>
`,V=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<rect x="3" y="3" width="18" height="18" rx="5" /><circle cx="12" cy="12" r="4" /><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none" />
</svg>
`,H=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M9 8h3l-2 8c-.2.8.8 1 1.2.3l2-3.8c.9-1.8 3.4-1 2.8.9l-.8 2.6c-.2.8.8 1 1.2.3" /><circle cx="11.7" cy="5" r="1" />
</svg>
`,L=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="m12 2 8.7 5v10L12 22l-8.7-5V7L12 2Zm0 2.3L5.3 8v8l6.7 3.7 6.7-3.7V8L12 4.3Zm0 2.2 1.1 2.2 2.4.4-1.7 1.7.4 2.4-2.2-1.1-2.2 1.1.4-2.4-1.7-1.7 2.4-.4L12 6.5Z"/></svg>
`,A=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M3 4h8l2 3 2-3h6v16H3V4Zm2 2v12h14V6h-3l-3 4-3-4H5Zm2 2h2l3 3 3-3h2v8h-2v-5l-3 3-3-3v5H7V8Z"/></svg>
`,z=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-12h4v2.3M2 9h4v12H2zM4 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4z" />
</svg>
`,S=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="m12 2 9 5v10l-9 5-9-5V7l9-5Zm0 2.2L5 8v8l7 3.8 7-3.8V8l-7-3.8Zm-3.5 5.1h2v5.4a2.1 2.1 0 0 1-2.2 2.2c-.8 0-1.5-.2-2.1-.6l.8-1.6c.4.3.8.4 1.1.4.3 0 .4-.2.4-.5V9.3Zm4.1 0h2l2.2 3.6V9.3h1.9v7.5h-1.9l-2.3-3.7v3.7h-1.9V9.3Z"/></svg>
`,$=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2c-3.7 0-5.2 1.5-5.2 4v2.4h7.1v1.7H4.8C2.9 10.1 2 11.8 2 14.2c0 3.6 1.7 5 4.2 5h2.4v-3c0-2.1 1.6-3.7 3.8-3.7h5.2c1.8 0 3.2-1.5 3.2-3.3V6c0-2.5-2.4-4-6.8-4H12Zm-1.8 2.6a1 1 0 1 1 0 2 1 1 0 0 1 0-2ZM12 22c3.7 0 5.2-1.5 5.2-4v-2.4h-7.1v-1.7h9.1c1.9 0 2.8-1.7 2.8-4.1 0-1.1-.2-2-.5-2.7v2.1c0 2.7-2 4.8-4.7 4.8h-5.2c-1.2 0-2.3 1-2.3 2.3V18c0 2.5 2.4 4 6.8 4Zm1.8-2.6a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"/></svg>
`,N=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="2" fill="currentColor"/><ellipse cx="12" cy="12" rx="10" ry="4"/><ellipse cx="12" cy="12" rx="10" ry="4" transform="rotate(60 12 12)"/><ellipse cx="12" cy="12" rx="10" ry="4" transform="rotate(120 12 12)"/></svg>
`,O=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M8 17c-1 1-2.2 1.4-3.5 1.5 1.8.8 3 1.1 3.6 2 2.5-.8 5.3-.8 7.8 0 .6-.9 1.8-1.2 3.6-2-1.3-.1-2.5-.5-3.5-1.5 1.2-.8 2-2.1 2.5-3.5-1.5.3-2.2-.5-2.6-1.1.2-2.2-.1-6.7-3.9-6.7s-4.1 4.5-3.9 6.7c-.4.6-1.1 1.4-2.6 1.1C6 14.9 6.8 16.2 8 17Z" />
</svg>
`,q=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="m3 11 18-8-4 18-6-6-4 3 1-5 9-6-11 5-3-1Z" />
</svg>
`,E=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M21 6a8 8 0 0 1-2.4.7A4.1 4.1 0 0 0 11.5 10 11.6 11.6 0 0 1 3 5.7a4.1 4.1 0 0 0 1.3 5.5c-.7 0-1.3-.2-1.8-.5 0 2 1.4 3.7 3.3 4.1-.6.2-1.2.2-1.8.1a4.1 4.1 0 0 0 3.8 2.8A8.2 8.2 0 0 1 3 19.1 11.6 11.6 0 0 0 20.8 9.3V8.8A8.1 8.1 0 0 0 23 6.6c-.8.3-1.5.5-2.3.6A4 4 0 0 0 22.5 5 8.1 8.1 0 0 1 20 6a4.1 4.1 0 0 0-7 3.7" />
</svg>
`,F=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M4 3h5l11 18h-5L4 3ZM20 3 4 21" />
</svg>
`,D=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M21 8.2a3 3 0 0 0-2.1-2.1C17 5.5 12 5.5 12 5.5s-5 0-6.9.6A3 3 0 0 0 3 8.2 31 31 0 0 0 2.5 12a31 31 0 0 0 .5 3.8 3 3 0 0 0 2.1 2.1c1.9.6 6.9.6 6.9.6s5 0 6.9-.6a3 3 0 0 0 2.1-2.1 31 31 0 0 0 .5-3.8 31 31 0 0 0-.5-3.8Z" /><path d="m10 9 5 3-5 3V9Z" />
</svg>
`,G=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<rect x="3" y="7" width="18" height="13" rx="2" /><path d="M8 7V4h8v3M3 12h18M10 12v2h4v-2" />
</svg>
`,I=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M4 21V3h11v18M15 9h5v12M8 7h3M8 11h3M8 15h3M8 19h3M18 13v2M18 18v1" />
</svg>
`,J=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<rect x="3" y="5" width="18" height="16" rx="2" /><path d="M16 3v4M8 3v4M3 10h18" />
</svg>
`,K=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8h4l1.5-2h5L16 8h4v11H4z"/><circle cx="12" cy="13.5" r="3.5"/></svg>
`,P=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="m5 11 2-5h10l2 5M3 11h18v7H3v-7ZM6 18v2M18 18v2" /><circle cx="7" cy="14.5" r="1" /><circle cx="17" cy="14.5" r="1" />
</svg>
`,Q=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M3 4h2l2.2 10.5a2 2 0 0 0 2 1.5h7.7a2 2 0 0 0 2-1.6L20 8H6M10 21h.01M17 21h.01" />
</svg>
`,R=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M4 20V10h4v10H4ZM10 20V4h4v16h-4ZM16 20v-7h4v7h-4Z" />
</svg>
`,T=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<circle cx="12" cy="12" r="9" /><path d="m8 12 2.5 2.5L16 9" />
</svg>
`,U=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="m5 12 4 4L19 6" />
</svg>
`,W=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="m6 9 6 6 6-6"/>
</svg>
`,X=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="m15 18-6-6 6-6" />
</svg>
`,Y=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="m9 18 6-6-6-6" />
</svg>
`,oo=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="m6 15 6-6 6 6" stroke="currentColor" stroke-width="1.8"/>
</svg>
`,no=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<circle cx="12" cy="12" r="9" /><circle cx="12" cy="12" r="3" /><path d="M3.8 8h8.1M19.7 7.5 15.6 14M8.1 20.1l4-7" />
</svg>
`,to=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="5" width="14" height="16" rx="2.5"/><path d="M9 5V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v1m-6 5h6m-6 4h6"/></svg>
`,ro=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" />
</svg>
`,eo=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M12 5a4 4 0 0 1 7.4 2M17 2v2M21.2 3.8l-1.4 1.4M22 8h-2" /><path d="M7 19h11a4 4 0 0 0 .4-8A6 6 0 0 0 7 12a3.5 3.5 0 0 0 0 7Z" />
</svg>
`,so=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 18H6a4 4 0 0 1-.3-8A6.5 6.5 0 0 1 18 8.5a4.5 4.5 0 0 1 0 9.5h-1"/><path d="M12 19v-8m-3 3 3-3 3 3"/></svg>
`,io=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="m7 7-5 5 5 5m10-10 5 5-5 5m-3-13-4 20" />
</svg>
`,lo=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<circle cx="9" cy="10" r="3" /><path d="M9 4v2M9 14v2M3 10h2M13 10h2M4.8 5.8l1.4 1.4M11.8 12.8l1.4 1.4M4.8 14.2l1.4-1.4M11.8 7.2l1.4-1.4" /><circle cx="17" cy="17" r="2.5" /><path d="M17 12.5v2M17 19.5v2M12.5 17h2M19.5 17h2M13.8 13.8l1.4 1.4M18.8 18.8l1.4 1.4M13.8 20.2l1.4-1.4M18.8 15.2l1.4-1.4" />
</svg>
`,vo=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M4 12h6m-3-3 3 3-3 3m13-3h-6m3-3-3 3 3 3"/></svg>
`,co=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9Z" /><path d="M14 3v6h6M8 13h8M8 17h6" />
</svg>
`,go=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="8" width="12" height="12" rx="2.5"/><path d="M16 8V6.5A2.5 2.5 0 0 0 13.5 4h-7A2.5 2.5 0 0 0 4 6.5v7A2.5 2.5 0 0 0 6.5 16H8"/></svg>
`,wo=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<rect x="6" y="6" width="12" height="12" rx="2" /><rect x="9" y="9" width="6" height="6" /><path d="M9 1v5M15 1v5M9 18v5M15 18v5M1 9h5M1 15h5M18 9h5M18 15h5" />
</svg>
`,ho=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<rect x="3" y="5" width="18" height="14" rx="2" /><path d="M3 10h18M7 15h3" />
</svg>
`,_o=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m5 3 13 12-6 .8-3 5.2z"/><path d="m12 15 3.5 5"/></svg>
`,ao=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<rect x="3" y="3" width="7" height="7" rx="1" /><rect x="14" y="3" width="7" height="7" rx="1" />
        <rect x="3" y="14" width="7" height="7" rx="1" /><rect x="14" y="14" width="7" height="7" rx="1" />
</svg>
`,ko=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="m3 8 4-5h10l4 5-9 13L3 8Z" /><path d="m7 3 5 18 5-18M3 8h18" />
</svg>
`,po=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M12 2v20M17 5.5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
</svg>
`,uo=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M12 3v12M7 10l5 5 5-5M4 20h16" />
</svg>
`,xo=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M12 2S5 9 5 15a7 7 0 0 0 14 0c0-6-7-13-7-13Z" />
</svg>
`,bo=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4L16.5 3.5Z" />
</svg>
`,Mo=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="m13 2-8 12h7l-1 8 8-12h-7l1-8Z" />
</svg>
`,jo=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M10 12H4m3-3-3 3 3 3m7-3h6m-3-3 3 3-3 3"/></svg>
`,mo=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M14 3h7v7M21 3 10 14M10 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-5"/>
</svg>
`,fo=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="m3 3 18 18M10.6 10.7a2 2 0 0 0 2.7 2.7M9.9 5.2A9.9 9.9 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-2 3M6.6 6.6C3.6 8.5 2 12 2 12s3.5 7 10 7a9.8 9.8 0 0 0 3.4-.6" />
</svg>
`,Co=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" />
</svg>
`,Bo=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 3h7l4 4v14h-11z"/><path d="M13.5 3v4h4M12 9v1m0 1.5v1m0 1.5v1m0 1.5v1"/></svg>
`,yo=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 3h7l4 4v14h-11z"/><path d="M13.5 3v4h4M14 10v5.3a1.6 1.6 0 1 1-1.5-1.6c.5 0 1 .2 1.5.5V12l3-1v3.3a1.6 1.6 0 1 1-1.5-1.6c.5 0 1 .2 1.5.5"/></svg>
`,Zo=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 3h7l4 4v14h-11z"/><path d="M13.5 3v4h4M9 11h6v5H9z"/><circle cx="10.7" cy="12.7" r=".65"/><path d="m9.5 15 1.6-1.5 1.2 1 1.1-.9 1.6 1.4"/></svg>
`,Vo=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 3h7l4 4v14h-11z"/><path d="M13.5 3v4h4"/><path d="M10.3 11a1.8 1.8 0 1 1 3.2 1.1c-.8.8-1.5 1.1-1.5 2.2m0 2.2v.1"/></svg>
`,Ho=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 3h7l4 4v14h-11z"/><path d="M13.5 3v4h4M8.5 10.5h7m-7 3h7m-7 3h7m-4.7-6v6m2.5-6v6"/></svg>
`,Lo=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9Z" /><path d="M14 3v6h6M8 13h8M8 17h8" />
</svg>
`,Ao=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 3h7l4 4v14h-11z"/><path d="M13.5 3v4h4M9 10h6v6H9z"/><path d="m11 11.7 2.5 1.3-2.5 1.3z"/></svg>
`,zo=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9Z"/><path d="M14 3v6h6"/>
</svg>
`,So=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M3 4h18l-7 8v7l-4 2v-9Z"/>
</svg>
`,$o=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="m3 9 9-6 9 6M5 10v8M10 10v8M14 10v8M19 10v8M3 21h18M2 18h20" />
</svg>
`,No=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M5 22V4M5 5c5-3 8 3 14 0v10c-6 3-9-3-14 0" />
</svg>
`,Oo=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M12 22c4.4 0 8-3.2 8-7.6 0-3.5-2-6.5-5.7-9.4.2 2.7-.6 4.3-2.3 5.5.2-3.5-1.3-6.3-4.4-8.5.2 4.4-3.6 6.4-3.6 11.8C4 18.5 7.6 22 12 22Z" />
</svg>
`,qo=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7.5V6a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v2"/><path d="M4 10h17l-2.2 9a2 2 0 0 1-2 1.5H5.5A2.5 2.5 0 0 1 3 18z"/><path d="M12 12v5m-2-2 2 2 2-2"/></svg>
`,Eo=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7.5V6a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9.5a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 17.5z"/><path d="M12 10v6m-3-3h6"/></svg>
`,Fo=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7.5V6a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v2"/><path d="M4 10h17l-2.2 9a2 2 0 0 1-2 1.5H5.5A2.5 2.5 0 0 1 3 18z"/><path d="M12 17v-5m-2 2 2-2 2 2"/></svg>
`,Do=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7.5V6a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9.5a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 17.5z"/></svg>
`,Go=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M19 4C11 4 6 8 6 15c7 0 11-5 13-11Z" /><path d="M5 20c2-6 6-9 11-12" />
</svg>
`,Io=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M17 5h4V1m-.8 4.5A9 9 0 1 0 21 15.8"/><text x="12" y="13" fill="currentColor" stroke="none" text-anchor="middle" dominant-baseline="middle" font-family="Arial,sans-serif" font-size="6.6" font-weight="700">10</text></svg>
`,Jo=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<circle cx="12" cy="12" r="9" /><path d="M8 9h.01M16 9h.01M8 17c1-2 2.3-3 4-3s3 1 4 3" />
</svg>
`,Ko=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5.6 16.8a8 8 0 1 1 12.8 0M12 12l4-3M5 19h14"/><circle cx="12" cy="12" r="1.4"/></svg>
`,Po=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<circle cx="12" cy="12" r="9"/><ellipse cx="12" cy="12" rx="4" ry="9"/><path d="M3 12h18M5 6h14M5 18h14"/>
</svg>
`,Qo=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="m6 9 2-5h8l2 5M4 9h16l2 10H2L4 9Z" /><path d="M8 14h8" />
</svg>
`,Ro=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="m2 9 10-5 10 5-10 5L2 9Z" /><path d="M6 11.5V16c2.6 2.7 9.4 2.7 12 0v-4.5M22 9v6" />
</svg>
`,To=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="5" r="1.7"/><circle cx="15" cy="5" r="1.7"/><circle cx="9" cy="12" r="1.7"/><circle cx="15" cy="12" r="1.7"/><circle cx="9" cy="19" r="1.7"/><circle cx="15" cy="19" r="1.7"/></svg>
`,Uo=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M3 14v-3a9 9 0 0 1 18 0v3M21 17v2a3 3 0 0 1-3 3h-4"/><rect x="2" y="11" width="4" height="8" rx="2"/><rect x="18" y="11" width="4" height="8" rx="2"/>
</svg>
`,Wo=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M8 4h8a4 4 0 0 1 0 8H8a4 4 0 0 1 0-8ZM8 12h8a4 4 0 0 1 0 8H8a4 4 0 0 1 0-8Z" /><path d="m8 4 8 16" />
</svg>
`,Xo=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z" />
</svg>
`,Yo=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<circle cx="12" cy="12" r="9" /><path d="M9.8 9a2.5 2.5 0 1 1 4.4 1.6c-1.3 1.2-2.2 1.4-2.2 3.4M12 18h.01" />
</svg>
`,on=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M4 21V4h16v17M2 21h20M9 8h6M12 5v6M8 21v-5h8v5" />
</svg>
`,nn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7v1"/>
</svg>
`,tn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 4 18 4 18 0V5M3 10c0 4 18 4 18 0M3 15c0 4 18 4 18 0"/>
</svg>
`,rn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="m10 13 4-4M8 10l-2 2a4 4 0 0 0 6 6l2-2M10 8l2-2a4 4 0 0 1 6 6l-2 2"/>
</svg>
`,en=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="m6 3 15 9-15 9Z"/>
</svg>
`,sn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M9 3v3H5a2 2 0 0 0-2 2v4h3a2 2 0 1 1 0 4H3v4a1 1 0 0 0 1 1h5v-3a2 2 0 1 1 4 0v3h4a2 2 0 0 0 2-2v-4h2a2 2 0 1 0 0-4h-2V7a1 1 0 0 0-1-1h-5V3a2 2 0 1 0-4 0Z"/>
</svg>
`,ln=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="18" height="18" rx="3" stroke="currentColor" stroke-width="1.7"/><path d="M9 3v18M15 3v18M5.5 7h1M11.5 7h1M17.5 7h1M5.5 11h1M11.5 11h1M5.5 15h1" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
`,vn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M6 9h.1M9 9h.1M12 9h.1M15 9h.1M18 9h.1M6 12h.1M9 12h.1M12 12h.1M15 12h.1M18 12h.1M8 15.5h8"/></svg>
`,cn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9.5"/><path d="M2.8 12h18.4M12 2.5c2.5 2.6 3.7 5.8 3.7 9.5s-1.2 6.9-3.7 9.5c-2.5-2.6-3.7-5.8-3.7-9.5S9.5 5.1 12 2.5z"/></svg>
`,gn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<rect x="3" y="4" width="18" height="16" rx="2" /><path d="M3 9h18" />
</svg>
`,wn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<rect x="3" y="4" width="18" height="16" rx="2" /><path d="M3 9h18M9 9v11" />
</svg>
`,hn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M20 4C11 4 5 8.8 5 16c0 2.2 1.8 4 4 4 7.2 0 11-7 11-16Z" /><path d="M4 21c2.5-6.2 6.8-10.2 13-13" />
</svg>
`,_n=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4"/><path d="m5.6 5.6 3.6 3.6m5.6 5.6 3.6 3.6m0-12.8-3.6 3.6m-5.6 5.6-3.6 3.6"/>
</svg>
`,dn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M10 13a4.5 4.5 0 0 0 6.4 0l2.1-2.1a4.5 4.5 0 0 0-6.4-6.4L11 5.6" /><path d="M14 11a4.5 4.5 0 0 0-6.4 0L5.5 13.1a4.5 4.5 0 0 0 6.4 6.4L13 18.4" />
</svg>
`,an=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M8 6h13M8 12h9M8 18h13M3 6h.01M3 12h.01M3 18h.01" />
</svg>
`,kn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z" /><circle cx="12" cy="10" r="2.5" />
</svg>
`,pn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<rect x="4" y="10" width="16" height="11" rx="2" /><path d="M8 10V7a4 4 0 0 1 8 0v3" />
</svg>
`,un=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M10 17 15 12 10 7M15 12H3M15 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4" />
</svg>
`,xn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<rect x="3" y="5" width="18" height="14" rx="2" /><path d="m3 7 9 6 9-6" />
</svg>
`,bn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M12 21s7-4.4 7-11a7 7 0 1 0-14 0c0 6.6 7 11 7 11Z" /><circle cx="12" cy="10" r="2.5" />
</svg>
`,Mn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3V6ZM9 3v15M15 6v15" />
</svg>
`,jn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3m13-5h3a2 2 0 0 1 2 2v3M3 16v3a2 2 0 0 0 2 2h3m8 0h3a2 2 0 0 0 2-2v-3"/></svg>
`,mn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<rect x="3" y="3" width="18" height="18" rx="2" /><circle cx="8.5" cy="8.5" r="1.5" /><path d="m21 15-5-5L5 21" />
</svg>
`,fn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="m3 11 16-6v14L3 13v-2ZM11.6 16.2 10 21H6l1.3-6.3" /><path d="M19 10a2 2 0 0 1 0 4" />
</svg>
`,Cn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M4 7h16M4 12h12M4 17h16" />
</svg>
`,Bn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8Z" />
</svg>
`,yn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="3" width="6" height="11" rx="3"/><path d="M6.5 11a5.5 5.5 0 0 0 11 0M12 16.5V21m-3 0h6"/></svg>
`,Zn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3v6H3m18 0h-6V3M3 15h6v6m12-6h-6v6"/></svg>
`,Vn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/></svg>
`,Hn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M20.5 15.2A9 9 0 0 1 8.8 3.5 9 9 0 1 0 20.5 15.2Z" />
</svg>
`,Ln=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/>
</svg>
`,An=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<circle cx="12" cy="5" r="1.5" fill="currentColor" stroke="none" /><circle cx="12" cy="12" r="1.5" fill="currentColor" stroke="none" /><circle cx="12" cy="19" r="1.5" fill="currentColor" stroke="none" />
</svg>
`,zn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<circle cx="5" cy="12" r="1" fill="currentColor" stroke="none" /><circle cx="12" cy="12" r="1" fill="currentColor" stroke="none" /><circle cx="19" cy="12" r="1" fill="currentColor" stroke="none" />
</svg>
`,Sn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m5 3 13 12-6 .8-3 5.2z"/><path d="m12 15 3.5 5"/></svg>
`,$n=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<rect x="3" y="4" width="18" height="16" rx="2" /><path d="M9 4v16" />
</svg>
`,Nn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="m16 6-8.5 8.5a2.5 2.5 0 0 0 3.5 3.5L19.5 9.5a4.5 4.5 0 0 0-6.4-6.4L4.5 12.1a6.5 6.5 0 0 0 9.2 9.2L20 15" />
</svg>
`,On=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<rect x="4" y="3" width="16" height="18" rx="2" /><circle cx="10" cy="10" r="2.5" /><path d="M7 16h6M16 7h2M16 11h2" />
</svg>
`,qn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M8 6v12M16 6v12"/></svg>
`,En=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c1 .3 1.9.6 2.9.7a2 2 0 0 1 1.7 2Z" />
</svg>
`,Fn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M21 12a9 9 0 1 1-9-9v9h9Z" /><path d="M15 3.5A9 9 0 0 1 20.5 9H15V3.5Z" />
</svg>
`,Dn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M5 3c7.7 0 13 5.3 16 16L3 21 5 3Z" /><path d="M5 8c5.2-.1 9.1 3.3 11.5 11.5M9 11h.01M12 16h.01" />
</svg>
`,Gn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M22 2 9.5 14.5M22 2l-7 20-4-8-8-4 19-8Z" />
</svg>
`,In=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="m8 5 11 7-11 7V5Z" />
</svg>
`,Jn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M12 5v14M5 12h14" />
</svg>
`,Kn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6v-7Z" />
</svg>
`,Pn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M19 13h-1.3a2.7 2.7 0 1 1 0-2H19a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v1.3a2.7 2.7 0 1 1-2 0V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h1.3a2.7 2.7 0 1 1 0 2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2v-1.3a2.7 2.7 0 1 1 2 0V19a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2Z" />
</svg>
`,Qn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<circle cx="12" cy="12" r="2" /><path d="M16.2 7.8a6 6 0 0 1 0 8.4M7.8 16.2a6 6 0 0 1 0-8.4M19.1 4.9a10 10 0 0 1 0 14.2M4.9 19.1a10 10 0 0 1 0-14.2" />
</svg>
`,Rn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M4 21V8l8-5 8 5v13M8 21v-5h8v5M8 10h2M14 10h2" />
</svg>
`,Tn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M20 6v5h-5M4 18v-5h5" /><path d="M18.5 9A7 7 0 0 0 6.2 6.2L4 9M5.5 15A7 7 0 0 0 17.8 17.8L20 15" />
</svg>
`,Un=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m17 4 3 3-3 3M20 7H7a4 4 0 0 0-4 4m4 9-3-3 3-3m-3 3h13a4 4 0 0 0 4-4"/></svg>
`,Wn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<ellipse cx="12" cy="12" rx="10" ry="4" /><ellipse cx="12" cy="12" rx="10" ry="4" transform="rotate(60 12 12)" /><ellipse cx="12" cy="12" rx="10" ry="4" transform="rotate(120 12 12)" /><circle cx="12" cy="12" r="1" />
</svg>
`,Xn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M7 5H3V1M3.8 5.5A9 9 0 1 1 3 15.8"/><text x="12" y="13" fill="currentColor" stroke="none" text-anchor="middle" dominant-baseline="middle" font-family="Arial,sans-serif" font-size="6.6" font-weight="700">10</text></svg>
`,Yn=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M3 10a9 9 0 1 1 2 8M3 4v6h6"/>
</svg>
`,ot=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<circle cx="11" cy="11" r="7" /><path d="m20 20-4-4" />
</svg>
`,nt=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<rect x="3" y="2" width="18" height="6" rx="2"/><rect x="3" y="9" width="18" height="6" rx="2"/><rect x="3" y="16" width="18" height="6" rx="2"/><path d="M6 5h.01M6 12h.01M6 19h.01M11 5h6M11 12h6M11 19h6"/>
</svg>
`,tt=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6v.2h-4V21a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14H2.8v-4H3a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1A1.7 1.7 0 0 0 9 4.6 1.7 1.7 0 0 0 10 3v-.2h4V3a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.2v4H21a1.7 1.7 0 0 0-1.6 1Z" />
</svg>
`,rt=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<circle cx="18" cy="5" r="3" /><circle cx="6" cy="12" r="3" /><circle cx="18" cy="19" r="3" /><path d="m8.6 10.5 6.8-4M8.6 13.5l6.8 4" />
</svg>
`,et=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" /><path d="m9 12 2 2 4-4" />
</svg>
`,st=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="m8 4-5 3 2 5 3-1v10h8V11l3 1 2-5-5-3a4 4 0 0 1-8 0Z" />
</svg>
`,it=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M14.5 10a1.5 1.5 0 1 0 3 0V8.5a1.5 1.5 0 0 0-3 0V10ZM10 14.5a1.5 1.5 0 1 0 0 3H8.5a1.5 1.5 0 0 0 0-3H10ZM13.5 14a1.5 1.5 0 0 0 0 3h1.5a1.5 1.5 0 0 0 0-3H13.5ZM10 6.5A1.5 1.5 0 1 0 10 4H8.5a1.5 1.5 0 0 0 0 3H10ZM6.5 10a1.5 1.5 0 0 0-3 0v1.5a1.5 1.5 0 0 0 3 0V10ZM10 13.5a1.5 1.5 0 0 0-3 0v1.5a1.5 1.5 0 0 0 3 0V13.5Z" />
</svg>
`,lt=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="m7 14 5 5 5-5M12 19V5" />
</svg>
`,vt=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="m7 10 5-5 5 5M12 5v14" />
</svg>
`,ct=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="m8 7 4-4 4 4M12 3v18M16 17l-4 4-4-4" />
</svg>
`,gt=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="m12 2.5 3 6.1 6.7 1-4.9 4.7 1.2 6.7-6-3.2-6 3.2 1.2-6.7-4.9-4.7 6.7-1 3-6.1Z" />
</svg>
`,wt=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<circle cx="12" cy="12" r="4" /><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
</svg>
`,ht=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<circle cx="12" cy="12" r="9" /><circle cx="12" cy="12" r="5" /><circle cx="12" cy="12" r="1" />
</svg>
`,_t=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<rect x="3" y="4" width="18" height="13" rx="2" /><path d="M8 21h8M12 17v4" />
</svg>
`,dt=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M3 9a3 3 0 0 0 0 6v4h18v-4a3 3 0 0 0 0-6V5H3v4Z" /><path d="M13 5v2M13 10v4M13 17v2" />
</svg>
`,at=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M3 6h18M8 6V4h8v2m-9 0 1 15h8l1-15M10 11v6m4-6v6"/>
</svg>
`,kt=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M4 7h16M9 7V4h6v3M6 7l1 14h10l1-14M10 11v6M14 11v6" />
</svg>
`,pt=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="m3 7 6 6 4-4 8 8M17 17h4v-4" />
</svg>
`,ut=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="m3 17 6-6 4 4 8-8M17 7h4v4" />
</svg>
`,xt=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<rect x="3" y="5" width="18" height="14" rx="2" /><path d="m8 2 4 3 4-3M8 22h8" />
</svg>
`,bt=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M22 5.9a8.4 8.4 0 0 1-2.4.7A4.2 4.2 0 0 0 12.4 10 12 12 0 0 1 3.7 5.6a4.2 4.2 0 0 0 1.3 5.6 4.1 4.1 0 0 1-1.9-.5v.1a4.2 4.2 0 0 0 3.4 4.1 4.3 4.3 0 0 1-1.9.1 4.2 4.2 0 0 0 3.9 2.9A8.4 8.4 0 0 1 3.3 20 11.8 11.8 0 0 0 9.7 22c7.7 0 11.9-6.4 11.9-11.9v-.5A8.5 8.5 0 0 0 24 7.4a8.2 8.2 0 0 1-2.4.7A4.2 4.2 0 0 0 23.4 5a8.6 8.6 0 0 1-2.7 1 4.2 4.2 0 0 0-7.2 3.8" />
</svg>
`,Mt=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M12 16V4M7 9l5-5 5 5M4 15v5h16v-5" />
</svg>
`,jt=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" /><circle cx="8.5" cy="7" r="4" /><path d="m17 11 2 2 4-4" />
</svg>
`,mt=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M15 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" /><circle cx="8" cy="7" r="4" /><path d="M19 8v6M16 11h6" />
</svg>
`,ft=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<circle cx="12" cy="8" r="4" /><path d="M4 21a8 8 0 0 1 16 0" />
</svg>
`,Ct=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8" />
</svg>
`,Bt=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<rect x="3" y="5" width="13" height="14" rx="2" /><path d="m16 10 5-3v10l-5-3v-4Z" />
</svg>
`,yt=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 10v4h4l5 4V6l-5 4z"/><path d="m17 10 4 4m0-4-4 4"/></svg>
`,Zt=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 10v4h4l5 4V6l-5 4z"/><path d="M17 9a4 4 0 0 1 0 6m2-9a8 8 0 0 1 0 12"/></svg>
`,Vt=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M5 12.5a10 10 0 0 1 14 0M8.5 16a5 5 0 0 1 7 0M12 20h.01M2 9a15 15 0 0 1 20 0" />
</svg>
`,Ht=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M3 8h12a3 3 0 1 0-3-3M3 12h17a3 3 0 1 1-3 3M3 16h8" />
</svg>
`,Lt=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="m18 6-12 12M6 6l12 12" />
</svg>
`,At=Object.assign({"../../icons/jobic/activity.svg":l,"../../icons/jobic/align-right.svg":v,"../../icons/jobic/apps.svg":c,"../../icons/jobic/archive.svg":g,"../../icons/jobic/arrow-left.svg":w,"../../icons/jobic/arrow-right.svg":h,"../../icons/jobic/arrows-left-right.svg":_,"../../icons/jobic/award.svg":d,"../../icons/jobic/bank.svg":a,"../../icons/jobic/bell.svg":k,"../../icons/jobic/book-open.svg":p,"../../icons/jobic/bookmark.svg":u,"../../icons/jobic/box.svg":x,"../../icons/jobic/brand-algolia.svg":b,"../../icons/jobic/brand-amazon.svg":M,"../../icons/jobic/brand-angular.svg":j,"../../icons/jobic/brand-css3.svg":m,"../../icons/jobic/brand-docker.svg":f,"../../icons/jobic/brand-dribbble.svg":C,"../../icons/jobic/brand-facebook.svg":B,"../../icons/jobic/brand-github.svg":y,"../../icons/jobic/brand-html5.svg":Z,"../../icons/jobic/brand-instagram.svg":V,"../../icons/jobic/brand-invision.svg":H,"../../icons/jobic/brand-kubernetes.svg":L,"../../icons/jobic/brand-laravel.svg":A,"../../icons/jobic/brand-linkedin.svg":z,"../../icons/jobic/brand-nodejs.svg":S,"../../icons/jobic/brand-python.svg":$,"../../icons/jobic/brand-react.svg":N,"../../icons/jobic/brand-snapchat.svg":O,"../../icons/jobic/brand-telegram.svg":q,"../../icons/jobic/brand-twitter.svg":E,"../../icons/jobic/brand-x.svg":F,"../../icons/jobic/brand-youtube.svg":D,"../../icons/jobic/briefcase.svg":G,"../../icons/jobic/building.svg":I,"../../icons/jobic/calendar.svg":J,"../../icons/jobic/camera.svg":K,"../../icons/jobic/car.svg":P,"../../icons/jobic/cart.svg":Q,"../../icons/jobic/chart-bar.svg":R,"../../icons/jobic/check-circle.svg":T,"../../icons/jobic/check.svg":U,"../../icons/jobic/chevron-down.svg":W,"../../icons/jobic/chevron-left.svg":X,"../../icons/jobic/chevron-right.svg":Y,"../../icons/jobic/chevron-up.svg":oo,"../../icons/jobic/chrome.svg":no,"../../icons/jobic/clipboard.svg":to,"../../icons/jobic/clock.svg":ro,"../../icons/jobic/cloud-sun.svg":eo,"../../icons/jobic/cloud-upload.svg":so,"../../icons/jobic/code.svg":io,"../../icons/jobic/cogs.svg":lo,"../../icons/jobic/collapse-in.svg":vo,"../../icons/jobic/content.svg":co,"../../icons/jobic/copy.svg":go,"../../icons/jobic/cpu.svg":wo,"../../icons/jobic/credit-card.svg":ho,"../../icons/jobic/cursor.svg":_o,"../../icons/jobic/dashboard.svg":ao,"../../icons/jobic/diamond.svg":ko,"../../icons/jobic/dollar.svg":po,"../../icons/jobic/download.svg":uo,"../../icons/jobic/droplet.svg":xo,"../../icons/jobic/edit.svg":bo,"../../icons/jobic/energy.svg":Mo,"../../icons/jobic/expand-out.svg":jo,"../../icons/jobic/external-link.svg":mo,"../../icons/jobic/eye-off.svg":fo,"../../icons/jobic/eye.svg":Co,"../../icons/jobic/file-archive.svg":Bo,"../../icons/jobic/file-audio.svg":yo,"../../icons/jobic/file-image.svg":Zo,"../../icons/jobic/file-question.svg":Vo,"../../icons/jobic/file-spreadsheet.svg":Ho,"../../icons/jobic/file-text.svg":Lo,"../../icons/jobic/file-video.svg":Ao,"../../icons/jobic/file.svg":zo,"../../icons/jobic/filter.svg":So,"../../icons/jobic/finance.svg":$o,"../../icons/jobic/flag.svg":No,"../../icons/jobic/flame.svg":Oo,"../../icons/jobic/folder-download.svg":qo,"../../icons/jobic/folder-plus.svg":Eo,"../../icons/jobic/folder-upload.svg":Fo,"../../icons/jobic/folder.svg":Do,"../../icons/jobic/food.svg":Go,"../../icons/jobic/forward-10.svg":Io,"../../icons/jobic/frown.svg":Jo,"../../icons/jobic/gauge.svg":Ko,"../../icons/jobic/globe.svg":Po,"../../icons/jobic/gold.svg":Qo,"../../icons/jobic/graduation.svg":Ro,"../../icons/jobic/grip-vertical.svg":To,"../../icons/jobic/headphones.svg":Uo,"../../icons/jobic/healthcare.svg":Wo,"../../icons/jobic/heart.svg":Xo,"../../icons/jobic/help-circle.svg":Yo,"../../icons/jobic/hospital.svg":on,"../../icons/jobic/info.svg":nn,"../../icons/jobic/installation-database.svg":tn,"../../icons/jobic/installation-link.svg":rn,"../../icons/jobic/installation-play.svg":en,"../../icons/jobic/installation-puzzle.svg":sn,"../../icons/jobic/kanban.svg":ln,"../../icons/jobic/keyboard.svg":vn,"../../icons/jobic/languages.svg":cn,"../../icons/jobic/layout-navbar.svg":gn,"../../icons/jobic/layout.svg":wn,"../../icons/jobic/leaf.svg":hn,"../../icons/jobic/life-buoy.svg":_n,"../../icons/jobic/link.svg":dn,"../../icons/jobic/list-tree.svg":an,"../../icons/jobic/location.svg":kn,"../../icons/jobic/lock.svg":pn,"../../icons/jobic/logout.svg":un,"../../icons/jobic/mail.svg":xn,"../../icons/jobic/map-pin.svg":bn,"../../icons/jobic/map.svg":Mn,"../../icons/jobic/maximize.svg":jn,"../../icons/jobic/media.svg":mn,"../../icons/jobic/megaphone.svg":fn,"../../icons/jobic/menu.svg":Cn,"../../icons/jobic/message.svg":Bn,"../../icons/jobic/microphone.svg":yn,"../../icons/jobic/minimize.svg":Zn,"../../icons/jobic/minus.svg":Vn,"../../icons/jobic/moon.svg":Hn,"../../icons/jobic/more-horizontal.svg":Ln,"../../icons/jobic/more-vertical.svg":An,"../../icons/jobic/more.svg":zn,"../../icons/jobic/mouse-pointer.svg":Sn,"../../icons/jobic/panel.svg":$n,"../../icons/jobic/paperclip.svg":Nn,"../../icons/jobic/passport.svg":On,"../../icons/jobic/pause.svg":qn,"../../icons/jobic/phone.svg":En,"../../icons/jobic/pie-chart.svg":Fn,"../../icons/jobic/pizza.svg":Dn,"../../icons/jobic/plane.svg":Gn,"../../icons/jobic/play.svg":In,"../../icons/jobic/plus.svg":Jn,"../../icons/jobic/printer.svg":Kn,"../../icons/jobic/puzzle.svg":Pn,"../../icons/jobic/radio.svg":Qn,"../../icons/jobic/real-estate.svg":Rn,"../../icons/jobic/refresh.svg":Tn,"../../icons/jobic/repeat.svg":Un,"../../icons/jobic/research.svg":Wn,"../../icons/jobic/rewind-10.svg":Xn,"../../icons/jobic/rotate.svg":Yn,"../../icons/jobic/search.svg":ot,"../../icons/jobic/server.svg":nt,"../../icons/jobic/settings.svg":tt,"../../icons/jobic/share.svg":rt,"../../icons/jobic/shield.svg":et,"../../icons/jobic/shirt.svg":st,"../../icons/jobic/slack.svg":it,"../../icons/jobic/sort-down.svg":lt,"../../icons/jobic/sort-up.svg":vt,"../../icons/jobic/sort.svg":ct,"../../icons/jobic/star.svg":gt,"../../icons/jobic/sun.svg":wt,"../../icons/jobic/target.svg":ht,"../../icons/jobic/technology.svg":_t,"../../icons/jobic/ticket.svg":dt,"../../icons/jobic/trash-2.svg":at,"../../icons/jobic/trash.svg":kt,"../../icons/jobic/trending-down.svg":pt,"../../icons/jobic/trending-up.svg":ut,"../../icons/jobic/tv.svg":xt,"../../icons/jobic/twitter.svg":bt,"../../icons/jobic/upload.svg":Mt,"../../icons/jobic/user-check.svg":jt,"../../icons/jobic/user-plus.svg":mt,"../../icons/jobic/user.svg":ft,"../../icons/jobic/users.svg":Ct,"../../icons/jobic/video.svg":Bt,"../../icons/jobic/volume-off.svg":yt,"../../icons/jobic/volume.svg":Zt,"../../icons/jobic/wifi.svg":Vt,"../../icons/jobic/wind.svg":Ht,"../../icons/jobic/x.svg":Lt}),zt=Object.fromEntries(Object.entries(At).map(([n,t])=>{const o=String(t).match(/<svg\b[^>]*>([\s\S]*?)<\/svg>/);return[n.split("/").pop().replace(/\.svg$/,""),(o==null?void 0:o[1])??""]}));function $t(n,t=18,o=""){const e=/^[a-z0-9-]+$/.test(String(n))?String(n):"",s=zt[e]??"",r=Number.isFinite(Number(t))?Math.max(1,Number(t)):18;return`<svg class="ui-icon ${String(o).split(/\s+/).filter(i=>/^[a-z0-9_-]+$/i.test(i)).join(" ")}" width="${r}" height="${r}" style="width:${r}px;height:${r}px" viewBox="0 0 24 24" fill="none" aria-hidden="true">${s}</svg>`}export{$t as i};
