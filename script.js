document.addEventListener("DOMContentLoaded", () => {
    let state = { username: null, token: null, gameId: null, role: null };
    let timer = null;

    const els = {
        loginScreen: document.getElementById('login-screen'),
        gameDash: document.getElementById('game-dashboard'),
        username: document.getElementById('username-input'),
        loginBtn: document.getElementById('btn-login'),
        loginMsg: document.getElementById('login-msg'),
        displayUser: document.getElementById('display-username'),
        createBtn: document.getElementById('btn-create'),
        joinBtn: document.getElementById('btn-join'),
        joinInput: document.getElementById('join-id'),
        statusMsg: document.getElementById('status-message'),
        myHand: document.getElementById('my-hand'),
        enemyHand: document.getElementById('enemy-hand'),
        table: document.getElementById('table-pile'),
        p1Pile: document.getElementById('p1-pile'),
        p2Pile: document.getElementById('p2-pile'),
        p1Xeri: document.getElementById('p1-xeres-list'),
        p2Xeri: document.getElementById('p2-xeres-list'),
        statsP1: { sc: document.getElementById('score-p1'), xr: document.getElementById('xeres-p1') },
        statsP2: { sc: document.getElementById('score-p2'), xr: document.getElementById('xeres-p2') }
    };

    async function api(url, method='GET', data=null) {
        try {
            const opts = { method, headers: {'Content-Type':'application/json'} };
            if(data) opts.body = JSON.stringify(data);
            const res = await fetch(url, opts);
            const txt = await res.text();
            const s = txt.indexOf('{'), e = txt.lastIndexOf('}');
            if(s !== -1 && e !== -1) return JSON.parse(txt.substring(s, e+1));
            throw new Error(txt);
        } catch(err) {
            console.error(err);
            return {status:'error', error:err.message};
        }
    }

    els.loginBtn.onclick = async () => {
        const u = els.username.value.trim();
        if(!u) return alert('Όνομα;');
        const d = await api('API/login.php', 'POST', {username:u});
        if(d.status === 'success') {
            state.username = d.username;
            state.token = d.token;
            els.displayUser.textContent = d.username;
            els.loginScreen.style.display = 'none';
            els.gameDash.style.display = 'block';
        } else els.loginMsg.textContent = d.error;
    };

    els.createBtn.onclick = async () => {
        const d = await api('API/start_game.php', 'POST', {token:state.token});
        if(d.status === 'success') {
            state.gameId = d.game_id;
            state.role = 'P1';
            els.statusMsg.innerHTML = `Game ID: <b style="color:gold">${d.game_id}</b>`;
            startLoop();
        }
    };

    els.joinBtn.onclick = async () => {
        const id = els.joinInput.value.trim();
        const d = await api('API/join_game.php', 'POST', {game_id:id});
        if(d.status === 'success') {
            state.gameId = d.game_id;
            state.role = 'P2';
            els.statusMsg.textContent = "Συνδέθηκες!";
            startLoop();
        } else alert(d.error);
    };

    function startLoop() {
        if(timer) clearInterval(timer);
        refresh();
        timer = setInterval(refresh, 2000);
    }

    async function refresh() {
        if(!state.gameId) return;
        const d = await api(`API/game_status.php?game_id=${state.gameId}`);
        if(d.status === 'success') render(d);
    }

    function render(d) {
        els.myHand.innerHTML = ''; 
        els.enemyHand.innerHTML = ''; 
        els.table.innerHTML = '';
        els.p1Xeri.innerHTML = ''; els.p2Xeri.innerHTML = '';

        els.statsP1.sc.textContent = d.scores.p1;
        els.statsP1.xr.textContent = d.scores.p1_xeres;
        els.statsP2.sc.textContent = d.scores.p2;
        els.statsP2.xr.textContent = d.scores.p2_xeres;

        const isMyTurn = (state.role==='P1' && d.turn===1) || (state.role==='P2' && d.turn===2);
        els.statusMsg.style.color = isMyTurn ? '#4CAF50' : '#f44336';
        els.statusMsg.textContent = isMyTurn ? "Η ΣΕΙΡΑ ΣΟΥ!" : `Σειρά Αντιπάλου`;

        // P1 HAND
        d.p1_hand.forEach(c => {
            if(state.role === 'P1') els.myHand.appendChild(mkCard(c.rank, c.suit, c.id, true)); 
        });
        
        // P2 HAND
        for(let i=0; i<d.p2_hand_count; i++) {
            const c = document.createElement('div');
            c.className = 'card';
            c.style.backgroundImage = "url('cards/back.png')";
            if(state.role === 'P1') els.enemyHand.appendChild(c);
            else els.myHand.appendChild(mkCard('?', '?', 0, true)); 
        }

        d.table_cards.forEach(c => els.table.appendChild(mkCard(c.rank, c.suit, c.id, false)));

        updatePile(els.p1Pile, d.captured.p1);
        updatePile(els.p2Pile, d.captured.p2);
        d.xeres_cards.p1.forEach(c => els.p1Xeri.appendChild(mkXeri(c.rank, c.suit)));
        d.xeres_cards.p2.forEach(c => els.p2Xeri.appendChild(mkXeri(c.rank, c.suit)));
    }

    function updatePile(el, count) {
        if(count > 0) { el.className = 'captured-pile has-cards'; el.innerHTML = ''; }
        else { el.className = 'captured-pile'; el.innerHTML = ''; }
    }

    function mkCard(val, suit, cardId, click) {
        const d = document.createElement('div');
        d.className = 'card';
        if(val !== '?') {
            const fname = `cards/${fn(val)}_${fn(suit)}.png`;
            d.style.backgroundImage = `url('${fname}')`;
        }
        if(click) {
            d.classList.add('clickable');
            d.onclick = () => doMove(cardId); 
        }
        return d;
    }

    function mkXeri(val, suit) {
        const d = document.createElement('div');
        d.className = 'xeri-card';
        const fname = `cards/${fn(val)}_${fn(suit)}.png`;
        d.style.backgroundImage = `url('${fname}')`;
        return d;
    }

    function fn(v) {
        if(v==='A') return 'ace'; if(v==='K') return 'king';
        if(v==='Q') return 'queen'; if(v==='J') return 'jack';
        if(v==='♣' || v==='C') return 'of_clubs'; 
        if(v==='♦' || v==='D') return 'of_diamonds'; 
        if(v==='♥' || v==='H') return 'of_hearts'; 
        if(v==='♠' || v==='S') return 'of_spades'; 
        return v; 
    }

    async function doMove(cardId) {
        if(els.statusMsg.textContent.includes('Αντιπάλου')) return alert('Περίμενε!');
        const d = await api('API/play_card.php', 'POST', {
            game_id: state.gameId, 
            card_id: cardId 
        });
        if(d.status === 'success') refresh();
        else alert(d.error);
    }
});