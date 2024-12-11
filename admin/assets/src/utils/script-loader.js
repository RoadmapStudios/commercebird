export function loadScript(src, id, nonce = "") {
    return new Promise((resolve, reject) => {
      if (document.getElementById(id)) {
        resolve(); // Script is already loaded
        return;
      }
      const script = document.createElement("script");
      script.type = "text/javascript";
      script.id = id;
      script.defer = true;
      if (nonce) script.nonce = nonce;
      script.src = src;
      script.onload = () => resolve();
      script.onerror = () => reject(`Failed to load script: ${src}`);
      document.head.appendChild(script);
    });
  }