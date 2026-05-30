const BASE_URL = "http://127.0.0.1:8000/api/v1";

let token    = localStorage.getItem("wowo_token")    || null;
let userRole = localStorage.getItem("wowo_role")     || null;
let userName = localStorage.getItem("wowo_username") || null;

// ─── Axios instance ─────────────────────────────────────────────────────────
function getApi() {
  return axios.create({
    baseURL: BASE_URL,
    headers: {
      "Content-Type":  "application/json",
      "Accept":        "application/json",
      "Authorization": token ? `Bearer ${token}` : "",
    },
  });
}

// ─── Inisialisasi: cek kalau sudah login ─────────────────────────────────────
window.addEventListener("DOMContentLoaded", () => {
  if (token) {
    showApp();
  } else {
    showLogin();
  }
});

function showLogin() {
  document.getElementById("loginSection").style.display = "block";
  document.getElementById("appSection").style.display   = "none";
}

function showApp() {
  document.getElementById("loginSection").style.display = "none";
  document.getElementById("appSection").style.display   = "block";
  document.getElementById("userInfo").innerText         = `👤 ${userName} (${userRole})`;


  if (userRole === "admin") {
    document.getElementById("formSection").style.display = "block";
  } else {
    document.getElementById("formSection").style.display = "none";
  }

  loadContainers();
}


async function doLogin() {
  const email    = document.getElementById("loginEmail").value;
  const password = document.getElementById("loginPassword").value;
  document.getElementById("loginError").innerText = "";

  try {
    const res = await axios.post(`${BASE_URL}/login`, { email, password }, {
      headers: { "Content-Type": "application/json", "Accept": "application/json" },
    });

    token    = res.data.token;
    userRole = res.data.user.role;
    userName = res.data.user.name;

    localStorage.setItem("wowo_token",    token);
    localStorage.setItem("wowo_role",     userRole);
    localStorage.setItem("wowo_username", userName);

    showApp();
  } catch (err) {
    document.getElementById("loginError").innerText =
      err.response?.data?.message || "Login gagal";
  }
}


async function doLogout() {
  try { await getApi().post("/logout"); } catch (_) {}
  token = null; userRole = null; userName = null;
  localStorage.removeItem("wowo_token");
  localStorage.removeItem("wowo_role");
  localStorage.removeItem("wowo_username");
  document.getElementById("containerList").innerHTML = "";
  showLogin();
}


const containerList  = document.getElementById("containerList");
const totalWeightEl  = document.getElementById("totalWeight");

function calculateTotalWeight(data) {
  totalWeightEl.innerText = data.reduce((sum, item) => sum + Number(item.weight_kg), 0);
}

async function loadContainers() {
  try {
    const res = await getApi().get("/gateway/containers");
    renderContainers(res.data);
    calculateTotalWeight(res.data);
  } catch (err) {
    if (err.response?.status === 401) { doLogout(); return; }
    alert("Gagal mengambil data kontainer");
  }
}

async function renderContainers(data) {
  containerList.innerHTML = "";
  if (data.length === 0) {
    containerList.innerHTML = "<p>Tidak ada data kontainer.</p>";
    totalWeightEl.innerText = "0";
    return;
  }

  for (const item of data) {
    const logs     = item.tracking_logs || [];
    const logsHtml = logs.length === 0
      ? "<p>Tidak ada log.</p>"
      : `<ul>${logs.map(log => `<li><strong>${log.location}</strong> - ${log.timestamp}<br>${log.description}</li>`).join("")}</ul>`;


    const adminBtns = userRole === "admin"
      ? `<button class="btn-warning" onclick="archiveContainer(${item.id})">Archive</button>
         <button class="btn-danger"  onclick="deleteContainer(${item.id})">Delete</button>`
      : "";

    const card = document.createElement("div");
    card.className = "item-card";
    card.innerHTML = `
      <h3>${item.container_id}</h3>
      <p><strong>Type:</strong> ${item.waste_type}</p>
      <p><strong>Weight:</strong> ${item.weight_kg} kg</p>
      <p><strong>Status:</strong>
        <span class="badge ${item.status === 'Active' ? 'badge-active' : 'badge-archived'}">${item.status}</span>
      </p>
      <div style="display:flex;gap:10px;margin-top:10px;">${adminBtns}</div>
      <div class="logs"><strong>Tracking Logs:</strong>${logsHtml}</div>
    `;
    containerList.appendChild(card);
  }
}


function clearErrors() {
  ["container_id","waste_type","weight_kg","status"].forEach(f => {
    document.getElementById(`error_${f}`).innerText = "";
  });
}

function showErrors(errors) {
  clearErrors();
  Object.keys(errors).forEach(key => {
    const el = document.getElementById(`error_${key}`);
    if (el) el.innerText = errors[key][0];
  });
}

async function submitForm() {
  clearErrors();
  const payload = {
    container_id: document.getElementById("container_id").value,
    waste_type:   document.getElementById("waste_type").value,
    weight_kg:    document.getElementById("weight_kg").value,
    status:       document.getElementById("status").value,
  };

  try {
    await getApi().post("/gateway/containers", payload);
    document.getElementById("containerForm").reset();
    loadContainers();
    alert("Data berhasil ditambahkan");
  } catch (err) {
    if (err.response?.status === 422) {
      showErrors(err.response.data.errors);
    } else if (err.response?.status === 403) {
      alert("Akses ditolak: hanya admin yang bisa menambah kontainer");
    } else {
      alert("Terjadi kesalahan saat menyimpan data");
    }
  }
}


async function archiveContainer(id) {
  try {
    await getApi().patch(`/gateway/containers/${id}/archive`);
    alert("Kontainer berhasil di-archive");
    loadContainers();
  } catch (err) {
    if (err.response?.status === 403) alert("Akses ditolak: hanya admin");
    else if (err.response?.status === 404) alert("Data tidak ditemukan");
    else alert("Gagal mengubah status");
  }
}

async function deleteContainer(id) {
  if (!confirm("Yakin ingin menghapus kontainer ini?")) return;
  try {
    await getApi().delete(`/gateway/containers/${id}`);
    alert("Kontainer berhasil dihapus");
    loadContainers();
  } catch (err) {
    if (err.response?.status === 403) alert("Akses ditolak: hanya admin");
    else if (err.response?.status === 404) alert("Data tidak ditemukan");
    else alert("Gagal menghapus data");
  }
}


async function applyFilter() {
  const type      = document.getElementById("filterType").value;
  const minWeight = document.getElementById("minWeight").value;
  const params    = new URLSearchParams();
  if (type)      params.append("type",       type);
  if (minWeight) params.append("min_weight", minWeight);

  try {
    const res = await getApi().get(`/gateway/containers?${params.toString()}`);
    renderContainers(res.data);
    calculateTotalWeight(res.data);
  } catch (err) {
    alert("Gagal memfilter data");
  }
}