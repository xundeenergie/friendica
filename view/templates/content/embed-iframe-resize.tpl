{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<span class="embedded-media">
  <iframe class="embed" id="{{$id}}" srcdoc="&lt;!doctype html&gt;&lt;html&gt;&lt;head&gt;&lt;style&gt;html,body{margin:0;padding:0;height:100%;}&lt;/style&gt;&lt;/head&gt;&lt;body&gt;{{$src}}&lt;script&gt;
  function sendResize() {
      parent.postMessage({type:'resize',height:document.body.scrollHeight},'*');
  }
  window.onload = function() {
      sendResize();
      setTimeout(sendResize, 2000);
  };
  &lt;/script&gt;&lt;/body&gt;&lt;/html&gt;" width="{{$width}}" scrolling="no" frameborder="0" allow="fullscreen, picture-in-picture" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen sandbox="allow-same-origin allow-scripts"></iframe>
  <script>
    window.addEventListener('message', function(event) {
      if (event.data && event.data.type === 'resize') {
        var iframe = document.getElementById('{{$id}}');
        if (iframe) {
          iframe.style.height = event.data.height + 'px';
        }
      }
    });
  </script>
</span>