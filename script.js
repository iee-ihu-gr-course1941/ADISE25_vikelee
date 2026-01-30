document.addEventListener("DOMContentLoaded", () => {
    let state = { 
        username: null, token: null, gameId: null, role: null, 
        lastActionStr: "",
        cache: { hand: "", enemy: "", table: "", xeriP1: "", xeriP2: "", pileP1: -1, pileP2: -1, turn: -1 }
    };
    let timer = null;
    let gameEndedHandled = false;

    //  HTML
    const els = {
        loginScreen: document.getElementById('login-screen'),
        gameDash: document.getElementById('game-dashboard'),
        lobbyControls: document.getElementById('lobby-controls'),
        gameArea: document.getElementById('game-area'),
        waitingScreen: document.getElementById('waiting-screen'),
        waitingId: document.getElementById('waiting-id'),
        username: document.getElementById('username-input'),
        loginBtn: document.getElementById('btn-login'),
        createBtn: document.getElementById('btn-create'),
        joinBtn: document.getElementById('btn-join'),
        joinInput: document.getElementById('join-id'),
        displayUser: document.getElementById('display-username'),
        statusMsg: document.getElementById('status-message'),
        ghostCard: document.getElementById('ghost-card'),
        myHand: document.getElementById('my-hand'),
        enemyHand: document.getElementById('enemy-hand'),
        table: document.getElementById('table-pile'),
        pileBottom: document.getElementById('p1-pile'), 
        pileTop: document.getElementById('p2-pile'),
        xeriBottom: document.getElementById('p1-xeres-list'),
        xeriTop: document.getElementById('p2-xeres-list'),
        scP1: document.getElementById('score-p1'), xrP1: document.getElementById('xeres-p1'),
        scP2: document.getElementById('score-p2'), xrP2: document.getElementById('xeres-p2')
    };

    function getCardValue(rank) {
        const map = { 'K':13, 'Q':12, 'J':11, '10':10, '9':9, '8':8, '7':7, '6':6, '5':5, '4':4, '3':3, '2':2, 'A':1 };
        return map[rank] || 0;
    }

    async function api(url, method='GET', data=null) {
        try {
            const opts = { method, headers: {'Content-Type':'application/json'} };
            if(data) opts.body = JSON.stringify(data);
            const res = await fetch(url, opts);
            const txt = await res.text();
            try { return JSON.parse(txt); } catch(e) { console.error("JSON Error:", txt); return {status:'error'}; }
        } catch(err) { return {status:'error', error:err.message}; }
    }

    els.loginBtn.onclick = async () => {
        const u = els.username.value.trim();
        if(!u) return alert('Βάλε όνομα');
        const d = await api('API/login.php', 'POST', {username:u});
        if(d.status === 'success') {
            state.username = d.username; state.token = d.token;
            els.displayUser.textContent = d.username;
            els.loginScreen.style.display = 'none';
            els.gameDash.style.display = 'flex';
        } else alert(d.error);
    };

    els.createBtn.onclick = async () => {
        const d = await api('API/start_game.php', 'POST', {token:state.token});
        if(d.status === 'success') setupGame(d.game_id, 'P1');
    };

    els.joinBtn.onclick = async () => {
        const id = els.joinInput.value.trim();
        if(!id) return alert("Γράψε ένα Game ID");
        const d = await api('API/join_game.php', 'POST', {game_id:id});
        if(d.status === 'success') {
            setupGame(d.game_id, 'P2');
        } else {
            alert("ΣΦΑΛΜΑ: " + d.error);
            els.joinInput.value = '';
        }
    };

    function setupGame(gid, role) {
        state.gameId = gid; state.role = role;
        els.lobbyControls.style.display = 'none';
        document.getElementById('display-game-id').textContent = gid;
        document.getElementById('game-id-container').style.display = 'inline';
        startLoop();
    }

    function startLoop() {
        if(timer) clearInterval(timer);
        refresh();
        timer = setInterval(refresh, 1500); 
    }

    async function refresh() {
        if(!state.gameId || gameEndedHandled) return;
        const t = new Date().getTime();
        const d = await api(`API/game_status.php?game_id=${state.gameId}&t=${t}`);
        if(d && d.status === 'success') render(d);
    }

    function render(d) {
    
        if (d.game_status === 'ended') {
            if (gameEndedHandled) return;
            gameEndedHandled = true;
            
            if (timer) clearInterval(timer);
            timer = null;

            
            let myS = (state.role === 'P1') ? d.scores.p1 : d.scores.p2;
            let enS = (state.role === 'P1') ? d.scores.p2 : d.scores.p1;
            
           

            els.scP1.textContent = myS || 0;
           

            els.scP2.textContent = enS || 0;

            setTimeout(() => {
                let myScore = parseInt(myS || 0);
                let enScore = parseInt(enS || 0);
                let msg = (myScore > enScore) ? "🎉 ΚΕΡΔΙΣΕΣ!" : (myScore < enScore ? "😢 ΕΧΑΣΕΣ..." : "🤝 ΙΣΟΠΑΛΙΑ");

                alert(`${msg}\n\nΤΕΛΙΚΟ ΣΚΟΡ:\nΕσύ: ${myScore}\nΑντίπαλος: ${enScore}`);
                window.location.reload();
            }, 500);

            return;
        }

        if (d.game_status === 'waiting') {
            els.waitingScreen.style.display = 'flex';
            els.waitingId.textContent = state.gameId;
            els.gameArea.style.display = 'none';
            return; 
        } else {
            els.waitingScreen.style.display = 'none';
            els.gameArea.style.display = 'flex';
        }

       
        try {
            const p1Hand = d.p1_hand || [];
            const p2Hand = d.p2_hand || [];
            const tableCards = d.table_cards || [];
            
            let myData, enemyData;
            if (state.role === 'P1') {
                myData = { hand: p1Hand, pile: d.captured.p1, xeres: d.xeres_cards.p1, score: d.scores.p1, xrCount: d.scores.p1_xeres };
                enemyData = { hand: p2Hand, pile: d.captured.p2, xeres: d.xeres_cards.p2, score: d.scores.p2, xrCount: d.scores.p2_xeres };
            } else {
                myData = { hand: p2Hand, pile: d.captured.p2, xeres: d.xeres_cards.p2, score: d.scores.p2, xrCount: d.scores.p2_xeres };
                enemyData = { hand: p1Hand, pile: d.captured.p1, xeres: d.xeres_cards.p1, score: d.scores.p1, xrCount: d.scores.p1_xeres };
            }

            myData.hand.sort((a, b) => getCardValue(b.rank) - getCardValue(a.rank));

            const myHandStr = JSON.stringify(myData.hand);
            if (myHandStr !== state.cache.hand) {
                els.myHand.innerHTML = '';
                myData.hand.forEach(c => els.myHand.appendChild(mkCard(c.rank, c.suit, c.id, true)));
                state.cache.hand = myHandStr;
            }

            const enHandStr = JSON.stringify(enemyData.hand);
            if (enHandStr !== state.cache.enemy) {
                els.enemyHand.innerHTML = '';
                enemyData.hand.forEach(c => {
                    const b = document.createElement('div'); b.className = 'card'; b.style.backgroundImage = "url('cards/back.png')";
                    els.enemyHand.appendChild(b);
                });
                state.cache.enemy = enHandStr;
            }

            const tableStr = JSON.stringify(tableCards);
            if (tableStr !== state.cache.table) {
                els.table.innerHTML = '';
                tableCards.forEach((c, i) => {
                    let el = mkCard(c.rank, c.suit, c.id, false);
                    el.style.position = 'absolute';
                    el.style.left = '50%'; el.style.top = '50%';
                    el.style.marginLeft = '-35px'; el.style.marginTop = '-52.5px';
                    el.style.transform = `rotate(${(i%5-2)*8}deg)`;
                    el.style.zIndex = i;
                    els.table.appendChild(el);
                });
                state.cache.table = tableStr;
            }

            if (myData.pile !== state.cache.pileP1) { updatePile(els.pileBottom, myData.pile); state.cache.pileP1 = myData.pile; }
            if (enemyData.pile !== state.cache.pileP2) { updatePile(els.pileTop, enemyData.pile); state.cache.pileP2 = enemyData.pile; }
            
            els.xeriBottom.innerHTML = ''; d.xeres_cards[state.role.toLowerCase()].forEach(c => els.xeriBottom.appendChild(mkXeri(c.rank, c.suit)));
            let enemyRole = state.role === 'P1' ? 'p2' : 'p1';
            els.xeriTop.innerHTML = ''; d.xeres_cards[enemyRole].forEach(c => els.xeriTop.appendChild(mkXeri(c.rank, c.suit)));

          
            els.scP1.textContent = myData.score || 0; 
            els.xrP1.textContent = myData.xrCount || 0;

            
            els.scP2.textContent = enemyData.score || 0; 
            els.xrP2.textContent = enemyData.xrCount || 0;

            if (state.cache.turn !== d.turn) {
                const isMyTurn = (state.role==='P1' && d.turn===1) || (state.role==='P2' && d.turn===2);
                els.statusMsg.style.display = "block";
                if (isMyTurn) {
                    els.statusMsg.innerHTML = "🟢 Η ΣΕΙΡΑ ΣΟΥ!";
                    els.statusMsg.style.backgroundColor = "#ff9800"; els.statusMsg.style.color = "white"; els.statusMsg.style.border = "2px solid white";
                } else {
                    els.statusMsg.innerHTML = "⏳ ΣΕΙΡΑ ΑΝΤΙΠΑΛΟΥ...";
                    els.statusMsg.style.backgroundColor = "#d32f2f"; els.statusMsg.style.color = "#ffcccc"; els.statusMsg.style.border = "2px solid #ffcccc";
                }
                state.cache.turn = d.turn;
            }

            if (d.last_action && d.last_action !== state.lastActionStr) {
                state.lastActionStr = d.last_action;
                let parts = d.last_action.split(':');
                if (parts.length === 2 && (parts[0] === 'CAPTURE' || parts[0] === 'XERI')) {
                    let cardCode = parts[1];
                    let suit = cardCode.slice(-1);
                    let rank = cardCode.slice(0, -1);
                    let fname = `cards/${fn(rank)}_${fn(suit)}.png`;
                    els.ghostCard.style.backgroundImage = `url('${fname}')`;
                    els.ghostCard.className = 'ghost-capture'; 
                    els.ghostCard.style.display = 'block';
                    setTimeout(() => { els.ghostCard.style.display = 'none'; }, 1500);
                } else {
                    els.ghostCard.style.display = 'none';
                }
            }
        } catch (e) { console.error("Error in render:", e); }
    }

    function updatePile(el, count) {
        if(count > 0) { el.classList.add('has-cards'); el.innerHTML = ''; }
        else { el.classList.remove('has-cards'); el.innerHTML = ''; }
    }

    function mkCard(val, suit, cid, click) {
        const d = document.createElement('div'); d.className = 'card';
        if(val && suit) d.style.backgroundImage = `url('cards/${fn(val)}_${fn(suit)}.png')`;
        if(click) { d.classList.add('clickable'); d.onclick = () => doMove(cid); }
        return d;
    }

    function mkXeri(val, suit) {
        const d = document.createElement('div'); d.className = 'xeri-card';
        if(val && suit) d.style.backgroundImage = `url('cards/${fn(val)}_${fn(suit)}.png')`;
        return d;
    }

    function fn(v) {
        if(!v) return '';
        if(v==='A') return 'ace'; if(v==='K') return 'king'; if(v==='Q') return 'queen'; if(v==='J') return 'jack';
        if(v==='♣'||v==='C') return 'of_clubs'; if(v==='♦'||v==='D') return 'of_diamonds';
        if(v==='♥'||v==='H') return 'of_hearts'; if(v==='♠'||v==='S') return 'of_spades';
        return v;
    }

    async function doMove(cid) {
        if(els.statusMsg.innerHTML.includes('ΑΝΤΙΠΑΛΟΥ')) return;
        const d = await api('API/play_card.php', 'POST', {game_id: state.gameId, card_id: cid});
        if(d.status === 'success') refresh();
    }
    document.getElementById('btn-logout').onclick = () => location.reload();
});