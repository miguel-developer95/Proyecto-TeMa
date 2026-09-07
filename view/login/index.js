/* ============================================
   TENTACIONES MARLLY — DASHBOARD LOGIC
   ============================================ */

document.addEventListener('DOMContentLoaded', () => {

  /* ---------- SIDEBAR (mobile) ---------- */
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('overlay');
  const menuToggle = document.getElementById('menuToggle');
  const sidebarClose = document.getElementById('sidebarClose');

  const openSidebar = () => {
    sidebar.classList.add('open');
    overlay.classList.add('show');
  };
  const closeSidebar = () => {
    sidebar.classList.remove('open');
    overlay.classList.remove('show');
  };
  menuToggle.addEventListener('click', openSidebar);
  sidebarClose.addEventListener('click', closeSidebar);
  overlay.addEventListener('click', () => { closeSidebar(); closeAllDropdowns(); });

  /* ---------- NAV LINKS ---------- */
  const navLinks = document.querySelectorAll('.nav-link:not(.logout)');
  const pageTitle = document.getElementById('pageTitle');
  const eyebrow = document.querySelector('.eyebrow');

  navLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      if (link.id === 'openAddProduct') {
        openModal();
        return;
      }
      navLinks.forEach(l => l.classList.remove('active'));
      link.classList.add('active');
      const page = link.dataset.page;
      eyebrow.textContent = page;
      pageTitle.textContent = page === 'Panel principal' ? 'Buen día, Marlly' : page;
      if (page !== 'Panel principal') {
        showToast('info', `La sección "${page}" está en construcción 🍬`);
      }
      closeSidebar();
    });
  });

  /* ---------- THEME TOGGLE ---------- */
  const themeToggle = document.getElementById('themeToggle');
  const themeIcon = document.getElementById('themeIcon');
  themeToggle.addEventListener('click', () => {
    document.body.classList.toggle('dark');
    const isDark = document.body.classList.contains('dark');
    themeIcon.textContent = isDark ? 'light_mode' : 'dark_mode';
  });

  /* ---------- DROPDOWNS ---------- */
  const notifBtn = document.getElementById('notifBtn');
  const notifDropdown = document.getElementById('notifDropdown');
  const profileBtn = document.getElementById('profileBtn');
  const profileDropdown = document.getElementById('profileDropdown');
  const notifDot = document.getElementById('notifDot');
  const clearNotifs = document.getElementById('clearNotifs');

  function closeAllDropdowns() {
    notifDropdown.classList.remove('open');
    profileDropdown.classList.remove('open');
  }

  notifBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    profileDropdown.classList.remove('open');
    notifDropdown.classList.toggle('open');
  });
  profileBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    notifDropdown.classList.remove('open');
    profileDropdown.classList.toggle('open');
  });
  document.addEventListener('click', closeAllDropdowns);
  notifDropdown.addEventListener('click', (e) => e.stopPropagation());
  profileDropdown.addEventListener('click', (e) => e.stopPropagation());

  clearNotifs.addEventListener('click', () => {
    document.querySelectorAll('.notif-list li.unread').forEach(li => li.classList.remove('unread'));
    notifDot.style.display = 'none';
  });

  /* ---------- LOGOUT ---------- */
  ['logoutBtn', 'logoutBtn2'].forEach(id => {
    document.getElementById(id).addEventListener('click', (e) => {
      e.preventDefault();
      showToast('logout', 'Cerrando sesión... ¡Hasta pronto! 👋');
    });
  });

  /* ---------- ANIMATED STAT COUNTERS ---------- */
  document.querySelectorAll('.stat-value').forEach(el => {
    const target = parseInt(el.dataset.count, 10);
    const prefix = el.dataset.prefix || '';
    const duration = 1100;
    const start = performance.now();
    function tick(now) {
      const progress = Math.min((now - start) / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3);
      const value = Math.floor(eased * target);
      el.textContent = prefix + value.toLocaleString('es-CO');
      if (progress < 1) requestAnimationFrame(tick);
      else el.textContent = prefix + target.toLocaleString('es-CO');
    }
    requestAnimationFrame(tick);
  });

  /* ---------- BAR CHART ---------- */
  const barChart = document.getElementById('barChart');
  const chartTabs = document.querySelectorAll('#chartTabs .tab');

  const chartData = {
    '7d':  { labels:['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'], values:[62,80,55,90,74,98,68] },
    '30d': { labels:['S1','S2','S3','S4'], values:[70,85,60,95] },
    '1a':  { labels:['Ene','Mar','May','Jul','Sep','Nov'], values:[50,65,72,80,60,90] }
  };

  function renderBarChart(range) {
    const data = chartData[range];
    const max = Math.max(...data.values);
    barChart.innerHTML = '';
    data.values.forEach((v, i) => {
      const col = document.createElement('div');
      col.className = 'bar-col';
      const bar = document.createElement('div');
      bar.className = 'bar';
      bar.style.height = '0%';
      bar.dataset.value = '$' + (v * 21000).toLocaleString('es-CO');
      const label = document.createElement('span');
      label.textContent = data.labels[i];
      col.appendChild(bar);
      col.appendChild(label);
      barChart.appendChild(col);
      requestAnimationFrame(() => {
        setTimeout(() => { bar.style.height = (v / max * 100) + '%'; }, i * 40);
      });
    });
  }

  chartTabs.forEach(tab => {
    tab.addEventListener('click', () => {
      chartTabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      renderBarChart(tab.dataset.range);
    });
  });

  renderBarChart('7d');

  /* ---------- DONUT CHART ---------- */
  const donutChart = document.getElementById('donutChart');
  const legendList = document.getElementById('legendList');

  const inventory = [
    { name:'Chocolates', pct:34, color:'#EA76A0' },
    { name:'Gomitas',    pct:24, color:'#F0A6C3' },
    { name:'Bombones',   pct:20, color:'#366E97' },
    { name:'Paletas',    pct:14, color:'#6FA0BF' },
    { name:'Combos',     pct:8,  color:'#B7CFDC' },
  ];

  function renderDonut() {
    let acc = 0;
    const stops = inventory.map(cat => {
      const start = acc;
      acc += cat.pct;
      return `${cat.color} ${start}% ${acc}%`;
    }).join(', ');
    requestAnimationFrame(() => {
      donutChart.style.background = `conic-gradient(${stops})`;
    });

    legendList.innerHTML = inventory.map(cat => `
      <li>
        <span class="dot" style="background:${cat.color}"></span>
        ${cat.name}
        <span class="pct">${cat.pct}%</span>
      </li>
    `).join('');
  }
  renderDonut();

  /* ---------- ORDERS TABLE ---------- */
  const orders = [
    { id:'#1042', client:'Ana Rojas',    product:'Bombones de fresa',   total:45000, status:'pendiente' },
    { id:'#1041', client:'Carlos Ruiz',  product:'Combo San Valentín',  total:120000, status:'proceso' },
    { id:'#1040', client:'Laura Gómez',  product:'Gomitas surtidas',    total:28000, status:'enviado' },
    { id:'#1039', client:'Pedro Silva',  product:'Chocolate 70% cacao', total:36000, status:'enviado' },
    { id:'#1038', client:'Marta Díaz',   product:'Paletas de caramelo', total:22000, status:'proceso' },
    { id:'#1037', client:'Julián Pérez', product:'Combo cumpleaños',    total:98000, status:'enviado' },
  ];

  const ordersBody = document.getElementById('ordersBody');
  const orderSearch = document.getElementById('orderSearch');

  function renderOrders(list) {
    if (!list.length) {
      ordersBody.innerHTML = `<tr><td colspan="5" class="no-results">No se encontraron pedidos que coincidan 🍭</td></tr>`;
      return;
    }
    ordersBody.innerHTML = list.map(o => `
      <tr>
        <td><strong>${o.id}</strong></td>
        <td>${o.client}</td>
        <td>${o.product}</td>
        <td>$${o.total.toLocaleString('es-CO')}</td>
        <td><span class="status ${o.status}">${capitalize(o.status)}</span></td>
      </tr>
    `).join('');
  }

  function capitalize(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

  renderOrders(orders);

  orderSearch.addEventListener('input', () => {
    const q = orderSearch.value.trim().toLowerCase();
    const filtered = orders.filter(o =>
      o.client.toLowerCase().includes(q) ||
      o.product.toLowerCase().includes(q) ||
      o.id.toLowerCase().includes(q)
    );
    renderOrders(filtered);
  });

  /* global search does the same filter + jumps to table */
  document.getElementById('globalSearch').addEventListener('input', (e) => {
    orderSearch.value = e.target.value;
    orderSearch.dispatchEvent(new Event('input'));
  });

  /* ---------- TOP PRODUCTS ---------- */
  const topProducts = [
    { name:'Bombones de fresa',   units:210, max:250 },
    { name:'Chocolate 70% cacao', units:184, max:250 },
    { name:'Gomitas surtidas',    units:150, max:250 },
    { name:'Combo San Valentín',  units:97,  max:250 },
  ];

  const topProductsList = document.getElementById('topProducts');
  topProductsList.innerHTML = topProducts.map((p, i) => `
    <li>
      <span class="tp-rank">${i + 1}</span>
      <div class="tp-info">
        <p>${p.name}</p>
        <div class="tp-bar"><div class="tp-bar-fill" style="width:0%" data-target="${(p.units / p.max) * 100}"></div></div>
      </div>
      <span class="tp-units">${p.units} u.</span>
    </li>
  `).join('');

  requestAnimationFrame(() => {
    setTimeout(() => {
      document.querySelectorAll('.tp-bar-fill').forEach(bar => {
        bar.style.width = bar.dataset.target + '%';
      });
    }, 200);
  });

  /* ---------- TICKER ---------- */
  const tickerMessages = [
    '🍬 Nuevo pedido #1042 de Ana Rojas',
    '📦 Pedido #1039 fue enviado',
    '⭐ Bombones de fresa es el producto más vendido esta semana',
    '💳 Pago confirmado de Carlos Ruiz',
    '🍫 Stock bajo: Bombones de fresa',
    '🎉 ¡Ventas del día superaron la meta en 12%!',
  ];
  const track = document.getElementById('tickerTrack');
  const content = tickerMessages.map(m => `<span>${m}</span>`).join('');
  track.innerHTML = content + content; // duplicated for seamless loop

  /* ---------- MODAL: ADD PRODUCT ---------- */
  const modalOverlay = document.getElementById('modalOverlay');
  const modalClose = document.getElementById('modalClose');
  const modalCancel = document.getElementById('modalCancel');
  const productForm = document.getElementById('productForm');

  function openModal() {
    modalOverlay.classList.add('open');
    document.getElementById('prodName').focus();
  }
  function closeModal() {
    modalOverlay.classList.remove('open');
    productForm.reset();
  }
  modalClose.addEventListener('click', closeModal);
  modalCancel.addEventListener('click', closeModal);
  modalOverlay.addEventListener('click', (e) => { if (e.target === modalOverlay) closeModal(); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

  productForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const name = document.getElementById('prodName').value.trim();
    const category = document.getElementById('prodCategory').value;
    closeModal();
    showToast('success', `"${name}" (${category}) se agregó al inventario ✔`);
  });

  /* ---------- TOASTS ---------- */
  const toastStack = document.getElementById('toastStack');
  const toastIcons = { success:'check_circle', info:'info', logout:'waving_hand' };

  function showToast(type, message) {
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = `<span class="material-icons-sharp">${toastIcons[type] || 'notifications'}</span><span>${message}</span>`;
    toastStack.appendChild(toast);
    setTimeout(() => {
      toast.classList.add('leaving');
      setTimeout(() => toast.remove(), 250);
    }, 3200);
  }

  /* Welcome toast */
  setTimeout(() => showToast('info', 'Bienvenida de nuevo, Marlly 🍭'), 600);

});