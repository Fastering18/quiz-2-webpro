// public/js/api.js - Core API Client & Helpers
// Shared across all dashboard pages for safe, authenticated REST requests.

(function (window) {
    'use strict';

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && meta.content) return meta.content;
        const cookieMatch = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        return cookieMatch ? decodeURIComponent(cookieMatch[1]) : "";
    }

    async function apiFetch(url, options = {}) {
        const headers = {
            "Accept": "application/json",
            "X-Requested-With": "XMLHttpRequest",
            ...(options.headers || {})
        };

        const token = localStorage.getItem("auth_token");
        if (token && !headers["Authorization"]) headers["Authorization"] = "Bearer " + token;

        const csrf = getCsrfToken();
        if (csrf) {
            headers["X-CSRF-TOKEN"] = csrf;
            headers["X-XSRF-TOKEN"] = csrf;
        }

        if (options.body && !(options.body instanceof FormData) && !headers["Content-Type"]) {
            headers["Content-Type"] = "application/json";
        }

        const config = {
            credentials: "same-origin",
            ...options,
            headers
        };

        const res = await fetch(url, config);

        if (res.status === 401) {
            localStorage.clear();
            window.location.href = "/";
            return null;
        }

        const contentType = res.headers.get("content-type") || "";
        let data = null;
        if (contentType.includes("application/json")) {
            data = await res.json();
        }

        if (!res.ok) {
            const err = new Error(data?.message || `HTTP ${res.status}`);
            err.status = res.status;
            err.data = data;
            throw err;
        }

        return data;
    }

    function escapeHtml(value) {
        if (value === null || value === undefined) return "";
        return String(value).replace(/[&<>"']/g, function (c) {
            return ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c];
        });
    }

    function formatFileSize(bytes) {
        if (!bytes || bytes === 0) return "0 B";
        if (bytes < 1024) return bytes + " B";
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + " KB";
        return (bytes / (1024 * 1024)).toFixed(2) + " MB";
    }

    function formatDate(dateStr) {
        if (!dateStr) return "-";
        try {
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;
            return d.toLocaleDateString("id-ID", { day: "numeric", month: "long", year: "numeric" });
        } catch (e) {
            return dateStr;
        }
    }

    // Expose helpers globally
    function fileHref(kind, id) {
        if (!kind || id === undefined || id === null || id === "") return "#";
        return "/files/" + encodeURIComponent(kind) + "/" + encodeURIComponent(String(id));
    }

    function currentAuthUser() {
        if (window.__AUTH_USER__) return window.__AUTH_USER__;
        try {
            const raw = localStorage.getItem("user");
            if (raw) return JSON.parse(raw);
        } catch (e) {}
        return null;
    }

    function peelRepeatedTitle(value) {
        let current = String(value || "").replace(/\s+/g, " ").trim();
        for (let i = 0; i < 4; i++) {
            const match = current.match(/^(.+?)\s*\((.+)\)\s*$/);
            if (!match) return current;
            const left = match[1].trim();
            const inner = match[2].trim();
            if (left && inner.toLowerCase().startsWith(left.toLowerCase())) {
                current = inner;
                continue;
            }
            return current;
        }
        return current;
    }

    function formatClassroomName(mkName, namaKelas) {
        const rawMk = String(mkName || "").replace(/\s+/g, " ").trim();
        const rawClass = peelRepeatedTitle(namaKelas);

        if (!rawClass && !rawMk) return "-";
        if (!rawClass) return rawMk;
        if (!rawMk) return rawClass;
        if (rawClass.toLowerCase() === rawMk.toLowerCase()) return rawMk;
        if (rawClass.toLowerCase().startsWith(rawMk.toLowerCase())) return rawClass;

        const section = rawClass.match(/^\(?([A-Za-z0-9]{1,3})\)?$/);
        if (section) return rawMk + " (" + section[1].toUpperCase() + ")";

        return rawMk + " (" + rawClass + ")";
    }

    function classroomSectionLabel(mkName, namaKelas) {
        const full = formatClassroomName(mkName, namaKelas);
        const rawMk = String(mkName || "").replace(/\s+/g, " ").trim();
        if (rawMk && full.toLowerCase().startsWith(rawMk.toLowerCase())) {
            const rest = full.slice(rawMk.length).trim();
            return rest || "-";
        }
        return full;
    }

    window.apiFetch = apiFetch;
    window.escapeHtml = escapeHtml;
    window.fileHref = fileHref;
    window.currentAuthUser = currentAuthUser;
    window.formatFileSize = formatFileSize;
    window.formatDate = formatDate;
    window.formatClassroomName = formatClassroomName;
    window.classroomSectionLabel = classroomSectionLabel;
    window.getCsrfToken = getCsrfToken;

})(window);
