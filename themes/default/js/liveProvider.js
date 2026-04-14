(function (window) {
  "use strict";

  class LiveProvider {
    async initClient() {}
    async joinChannel() {}
    async subscribe() {}
    async leave() {}
    async createMicrophoneTrack() {}
    async createCameraTrack() {}
    async publish() {}
    getClient() { return null; }
  }

  class AgoraLiveProvider extends LiveProvider {
    constructor(AgoraRTC) {
      super();
      this.AgoraRTC = AgoraRTC;
      this.client = null;
      this.remoteUsers = {};
      this.uid = null;
    }

    async initClient(options) {
      const { mode = "live", codec = "vp8", role = "audience", audienceLatency = 2, onUserPublished, onUserUnpublished } = options || {};
      this.client = this.AgoraRTC.createClient({ mode, codec });
      if (role === "audience") {
        this.client.setClientRole(role, { level: audienceLatency });
      } else {
        this.client.setClientRole(role);
      }
      if (onUserPublished) {
        this.client.on("user-published", onUserPublished);
      }
      if (onUserUnpublished) {
        this.client.on("user-unpublished", onUserUnpublished);
      }
    }

    async joinChannel({ appid, channel, token = null, uid = null }) {
      this.uid = await this.client.join(appid, channel, token, uid);
      return this.uid;
    }

    async subscribe(user, mediaType) {
      return this.client.subscribe(user, mediaType);
    }

    async leave() {
      if (this.client) {
        await this.client.leave();
      }
      this.remoteUsers = {};
      this.uid = null;
    }

    async createMicrophoneTrack(options) {
      return this.AgoraRTC.createMicrophoneAudioTrack(options);
    }

    async createCameraTrack(options) {
      return this.AgoraRTC.createCameraVideoTrack(options);
    }

    async publish(tracks) {
      if (this.client && tracks && tracks.length) {
        await this.client.publish(tracks);
      }
    }

    getClient() {
      return this.client;
    }
  }

  const LIVEKIT_CDN = "https://cdn.jsdelivr.net/npm/livekit-client@2/dist/livekit-client.umd.min.js";
  let liveKitLoader = null;

  function normalizeRealtimeWsUrl(value) {
    const rawValue = typeof value === "string" ? value.trim() : "";
    if (!rawValue) {
      return "";
    }
    if (/^wss?:\/\//i.test(rawValue)) {
      return rawValue;
    }
    if (/^https?:\/\//i.test(rawValue)) {
      try {
        const parsed = new URL(rawValue);
        parsed.protocol = parsed.protocol === "https:" ? "wss:" : "ws:";
        return parsed.toString();
      } catch (error) {
        return rawValue;
      }
    }
    if (!/^[a-z]+:\/\//i.test(rawValue) && /^[^/\s?#]+(\.[^/\s?#]+)+/.test(rawValue)) {
      return `wss://${rawValue}`;
    }
    return rawValue;
  }

  function loadLiveKitClient(src) {
    const globalClient = window.livekit || window.LivekitClient || window.livekitClient || window.LiveKitClient;
    if (globalClient) {
      return Promise.resolve(globalClient);
    }
    if (liveKitLoader) {
      return liveKitLoader;
    }
    liveKitLoader = new Promise((resolve, reject) => {
      const script = document.createElement("script");
      script.src = src || LIVEKIT_CDN;
      script.async = true;
      script.onload = () => resolve(window.LivekitClient || window.livekitClient || window.livekit || window.LiveKitClient);
      script.onerror = () => reject(new Error("LiveKit library failed to load"));
      document.head.appendChild(script);
    });
    return liveKitLoader;
  }

  function wrapRemoteTrack(track, mediaType) {
    return {
      __track: track,
      play: (elementId, options = {}) => {
        const element = typeof elementId === "string" ? document.getElementById(elementId) : elementId;
        const mediaEl = track.attach();
        mediaEl.autoplay = true;
        mediaEl.playsInline = true;
        mediaEl.style.width = "100%";
        mediaEl.style.height = "100%";
        mediaEl.style.objectFit = options.fit || "cover";
        if (element) {
          element.innerHTML = "";
          element.appendChild(mediaEl);
        } else {
          document.body.appendChild(mediaEl);
        }
      }
    };
  }

  function wrapLocalTrack(track, providerInstance) {
    const wrapper = {
      __track: track,
      play: (elementId) => {
        const mediaEl = track.attach ? track.attach() : null;
        if (!mediaEl) { return; }
        mediaEl.autoplay = true;
        mediaEl.playsInline = true;
        mediaEl.style.width = "100%";
        mediaEl.style.height = "100%";
        mediaEl.style.objectFit = "cover";
        if (elementId) {
          const el = typeof elementId === "string" ? document.getElementById(elementId) : elementId;
          if (el) {
            el.innerHTML = "";
            el.appendChild(mediaEl);
          }
        } else {
          mediaEl.style.display = "none";
          document.body.appendChild(mediaEl);
        }
      },
      stop: () => {
        track.stop?.();
        if (track.detach) {
          track.detach().forEach((el) => el.remove());
        }
      },
      close: () => {
        track.stop?.();
        if (track.detach) {
          track.detach().forEach((el) => el.remove());
        }
      },
      setMuted: async (muted) => {
        if (track.mute && track.unmute) {
          return muted ? track.mute() : track.unmute();
        }
        if (typeof track.enabled !== "undefined") {
          track.enabled = !muted;
        }
      },
      setDevice: async (deviceId) => {
        if (!deviceId || !providerInstance || !providerInstance.livekit) {
          return;
        }
        const isAudio = track.kind === "audio" || (providerInstance.livekit.Track && track.kind === providerInstance.livekit.Track.Kind.Audio);
        const factory = isAudio ? providerInstance.livekit.createLocalAudioTrack : providerInstance.livekit.createLocalVideoTrack;
        if (!factory) {
          return;
        }
        const newTrack = await factory.call(providerInstance.livekit, { deviceId });
        if (providerInstance.room && providerInstance.room.localParticipant) {
          try {
            await providerInstance.room.localParticipant.publishTrack(newTrack);
          } catch (err) {
            console.error(err);
          }
        }
        track.stop?.();
        track.detach?.().forEach((el) => el.remove());
        wrapper.__track = newTrack;
        track = newTrack;
      }
    };
    return wrapper;
  }

  class LiveKitProvider extends LiveProvider {
    constructor(config = {}) {
      super();
      this.wsUrl = normalizeRealtimeWsUrl(config.wsUrl || "");
      this.clientLoader = config.clientLoader || loadLiveKitClient;
      this.livekit = null;
      this.room = null;
      this.remoteUsers = {};
      this.uid = null;
      this.onUserPublished = null;
      this.onUserUnpublished = null;
      this.onUserLeft = null;
      this.onConnectionStateChange = null;
      this.boundRoom = null;
    }

    async ensureClient() {
      if (this.livekit) {
        return this.livekit;
      }
      this.livekit = await this.clientLoader();
      if (!this.livekit && (window.livekit || window.LivekitClient || window.livekitClient)) {
        this.livekit = window.livekit || window.LivekitClient || window.livekitClient;
      }
      return this.livekit;
    }

    resolveModuleExports() {
      const mod = this.livekit || {};
      const root = mod.default || mod;
      const globalMod = window.livekit || window.LivekitClient || window.livekitClient || window.LiveKitClient || {};
      const globalRoot = globalMod.default || globalMod;

      const candidates = [
        mod,
        root,
        globalMod,
        globalRoot,
        (mod || {}).LivekitClient,
        (globalMod || {}).LivekitClient,
        (window || {}).LivekitClient,
      ].filter(Boolean);

      const connectFn = candidates.map((m) => (typeof m.connect === "function" ? m.connect : null)).find(Boolean) || null;
      const RoomCtor = candidates.map((m) => m.Room).find(Boolean) || null;
      const RoomEvent = candidates.map((m) => m.RoomEvent).find(Boolean) || null;
      const Track = candidates.map((m) => m.Track).find(Boolean) || null;
      const createLocalAudioTrack = candidates.map((m) => m.createLocalAudioTrack).find((fn) => typeof fn === "function") || null;
      const createLocalVideoTrack = candidates.map((m) => m.createLocalVideoTrack).find((fn) => typeof fn === "function") || null;
      return { connectFn, RoomCtor, RoomEvent, Track, createLocalAudioTrack, createLocalVideoTrack };
    }

    async initClient(options) {
      this.onUserPublished = options?.onUserPublished || null;
      this.onUserUnpublished = options?.onUserUnpublished || null;
      this.onUserLeft = options?.onUserLeft || null;
      this.onConnectionStateChange = options?.onConnectionStateChange || null;
      await this.ensureClient();
    }

    resolveMediaType(track, Track) {
      const kind = (track && track.kind ? String(track.kind) : "").toLowerCase();
      if (Track && Track.Kind && (track?.kind === Track.Kind.Video || kind === "video")) {
        return "video";
      }
      return "audio";
    }

    getParticipantId(participant) {
      return participant?.identity || participant?.sid || null;
    }

    cacheRemoteTrack(track, participant, Track) {
      const userId = this.getParticipantId(participant);
      if (!userId) {
        return null;
      }
      const mediaType = this.resolveMediaType(track, Track);
      const remoteUser = this.remoteUsers[userId] || { uid: userId };
      const key = mediaType === "video" ? "videoTrack" : "audioTrack";
      const currentTrack = remoteUser[key] && remoteUser[key].__track ? remoteUser[key].__track : null;
      if (currentTrack === track) {
        this.remoteUsers[userId] = remoteUser;
        return { mediaType, remoteUser, changed: false };
      }
      remoteUser[key] = wrapRemoteTrack(track, mediaType);
      this.remoteUsers[userId] = remoteUser;
      return { mediaType, remoteUser, changed: true };
    }

    notifyExistingParticipantTracks(participant, Track) {
      if (!participant || !participant.trackPublications) {
        return;
      }
      participant.trackPublications.forEach((publication) => {
        if (!publication || !publication.track) {
          return;
        }
        const cached = this.cacheRemoteTrack(publication.track, participant, Track);
        if (cached && cached.changed && this.onUserPublished) {
          this.onUserPublished(cached.remoteUser, cached.mediaType);
        }
      });
    }

    syncExistingParticipants(Track) {
      if (!this.room || !this.room.remoteParticipants) {
        return;
      }
      this.room.remoteParticipants.forEach((participant) => {
        this.notifyExistingParticipantTracks(participant, Track);
      });
    }

    bindRoomEvents() {
      if (!this.room || !this.livekit || this.boundRoom === this.room) {
        return;
      }
      this.boundRoom = this.room;
      const { RoomEvent, Track } = this.resolveModuleExports();
      if (!RoomEvent || !Track) {
        return;
      }
      this.room.on(RoomEvent.TrackSubscribed, (track, publication, participant) => {
        const cached = this.cacheRemoteTrack(track, participant, Track);
        if (cached && cached.changed && this.onUserPublished) {
          this.onUserPublished(cached.remoteUser, cached.mediaType);
        }
      });
      this.room.on(RoomEvent.TrackUnsubscribed, (track, publication, participant) => {
        const mediaType = this.resolveMediaType(track, Track);
        const userId = this.getParticipantId(participant);
        if (!userId) {
          return;
        }
        if (this.remoteUsers[userId]) {
          if (mediaType === "video") {
            delete this.remoteUsers[userId].videoTrack;
          } else {
            delete this.remoteUsers[userId].audioTrack;
          }
        }
        if (this.onUserUnpublished) {
          this.onUserUnpublished({ uid: userId }, mediaType);
        }
      });
      this.room.on(RoomEvent.ParticipantConnected, (participant) => {
        this.notifyExistingParticipantTracks(participant, Track);
      });
      this.room.on(RoomEvent.ParticipantDisconnected, (participant) => {
        const userId = this.getParticipantId(participant);
        if (!userId) {
          return;
        }
        delete this.remoteUsers[userId];
        if (this.onUserLeft) {
          this.onUserLeft({ uid: userId });
        }
      });
      if (this.onConnectionStateChange && RoomEvent.ConnectionStateChanged) {
        this.room.on(RoomEvent.ConnectionStateChanged, (state) => {
          const nextState = String(state || "").toUpperCase();
          this.onConnectionStateChange(nextState, null, null);
        });
      }
    }

    async joinChannel({ token = null, uid = null, wsUrl = null }) {
      const lk = await this.ensureClient();
      const { connectFn, RoomCtor } = this.resolveModuleExports();
      if (wsUrl) {
        this.wsUrl = normalizeRealtimeWsUrl(wsUrl);
      }
      if (!this.wsUrl) {
        throw new Error("LiveKit WebSocket URL is missing");
      }
      if (RoomCtor) {
        const room = new RoomCtor();
        this.room = room;
        this.bindRoomEvents();
        await room.connect(this.wsUrl, token, { autoSubscribe: true });
      } else if (typeof connectFn === "function") {
        this.room = await connectFn(this.wsUrl, token, { autoSubscribe: true });
        this.bindRoomEvents();
      } else {
        throw new Error("LiveKit client is missing the connect function");
      }
      this.uid = uid || (this.room?.localParticipant?.identity ?? null);
      this.syncExistingParticipants(this.resolveModuleExports().Track);
      return this.uid;
    }

    async subscribe() {
      return Promise.resolve();
    }

    async leave() {
      this.remoteUsers = {};
      this.uid = null;
      if (this.room) {
        await this.room.disconnect();
        this.room = null;
      }
      this.boundRoom = null;
    }

    async createMicrophoneTrack(options) {
      const lk = await this.ensureClient();
      const { createLocalAudioTrack } = this.resolveModuleExports();
      const deviceId = options?.microphoneId || options?.deviceId;
      const factory = createLocalAudioTrack || lk.createLocalAudioTrack;
      if (!factory) {
        throw new Error("LiveKit audio track factory is missing");
      }
      const track = await factory(deviceId ? { deviceId } : undefined);
      return wrapLocalTrack(track, this);
    }

    async createCameraTrack(options) {
      const lk = await this.ensureClient();
      const { createLocalVideoTrack } = this.resolveModuleExports();
      const deviceId = options?.cameraId || options?.deviceId;
      const factory = createLocalVideoTrack || lk.createLocalVideoTrack;
      if (!factory) {
        throw new Error("LiveKit video track factory is missing");
      }
      const track = await factory(deviceId ? { deviceId } : undefined);
      return wrapLocalTrack(track, this);
    }

    async publish(tracks) {
      if (!this.room || !this.room.localParticipant || !tracks) {
        return;
      }
      const publishPromises = [];
      tracks.forEach((track) => {
        if (!track) {
          return;
        }
        const rawTrack = track.__track || track;
        publishPromises.push(this.room.localParticipant.publishTrack(rawTrack));
      });
      await Promise.all(publishPromises);
    }

    getClient() {
      return this.room;
    }
  }

  class VideoCallProvider {
    constructor(AgoraRTC) {
      this.AgoraRTC = AgoraRTC;
      this.client = null;
    }

    async initClient(options) {
      const { mode = "rtc", codec = "vp8", onUserPublished, onUserUnpublished, onUserLeft, onConnectionStateChange, onNetworkQuality, onTokenWillExpire, onTokenDidExpire } = options || {};
      this.client = this.AgoraRTC.createClient({ mode, codec });
      if (onUserPublished) {
        this.client.on("user-published", onUserPublished);
      }
      if (onUserUnpublished) {
        this.client.on("user-unpublished", onUserUnpublished);
      }
      if (onUserLeft) {
        this.client.on("user-left", onUserLeft);
      }
      if (onConnectionStateChange) {
        this.client.on("connection-state-change", onConnectionStateChange);
      }
      if (onNetworkQuality && this.client.on) {
        this.client.on("network-quality", onNetworkQuality);
      }
      if (onTokenWillExpire) {
        this.client.on("token-privilege-will-expire", onTokenWillExpire);
      }
      if (onTokenDidExpire) {
        this.client.on("token-privilege-did-expire", onTokenDidExpire);
      }
    }

    async joinChannel({ appid, channel, token = null, uid = null }) {
      return this.client.join(appid, channel, token, uid);
    }

    async subscribe(user, mediaType) {
      return this.client.subscribe(user, mediaType);
    }

    async createMicrophoneTrack(options) {
      return this.AgoraRTC.createMicrophoneAudioTrack(options);
    }

    async createCameraTrack(options) {
      return this.AgoraRTC.createCameraVideoTrack(options);
    }

    async publish(tracks) {
      if (this.client && tracks && tracks.length) {
        await this.client.publish(tracks);
      }
    }

    async leave() {
      if (this.client) {
        await this.client.leave();
      }
    }

    getClient() {
      return this.client;
    }
  }

  window.LiveProvider = LiveProvider;
  window.AgoraLiveProvider = AgoraLiveProvider;
  window.LiveKitProvider = LiveKitProvider;
  window.VideoCallProvider = VideoCallProvider;
  class IsometrikVideoCallProvider {
    constructor(config = {}) {
      this.wsUrl = config.wsUrl || "";
      this.client = null;
      this.uid = null;
      this.localTracks = [];
    }

    async initClient(options) {
      this.options = options || {};
    }

    async joinChannel({ token = null, uid = null, wsUrl = null }) {
      if (wsUrl) {
        this.wsUrl = wsUrl;
      }
      if (!this.wsUrl) {
        // Allow local-preview-only fallback when WS URL is not provided.
        console.warn("Isometrik WebSocket URL is missing; running local preview only.");
      }
      this.client = { connected: true, token };
      this.uid = uid || Math.floor(Math.random() * 1000000);
      return this.uid;
    }

    async subscribe() { return Promise.resolve(); }
    async createMicrophoneTrack() {
      return this.createMediaTrack('audio');
    }
    async createCameraTrack() {
      return this.createMediaTrack('video');
    }
    async createMediaTrack(kind) {
      const constraints = kind === 'video' ? { video: true } : { audio: true };
      const stream = await navigator.mediaDevices.getUserMedia(constraints);
      const track = kind === 'video' ? stream.getVideoTracks()[0] : stream.getAudioTracks()[0];
      const wrapper = {
        kind,
        __stream: stream,
        __track: track,
        play: (elementId, options = {}) => {
          const mediaEl = document.createElement(kind === 'video' ? 'video' : 'audio');
          mediaEl.autoplay = true;
          mediaEl.playsInline = true;
          if (kind === 'audio') { mediaEl.muted = true; }
          mediaEl.srcObject = stream;
          if (kind === 'video') {
            mediaEl.style.width = '100%';
            mediaEl.style.height = '100%';
            if (options.fit) { mediaEl.style.objectFit = options.fit; }
          }
          const target = typeof elementId === 'string' ? document.getElementById(elementId) : elementId;
          if (target) {
            target.innerHTML = '';
            target.appendChild(mediaEl);
          } else {
            document.body.appendChild(mediaEl);
          }
        },
        setMuted: async (muted) => { track.enabled = !muted; },
        stop: () => { track.stop(); stream.getTracks().forEach(t => t.stop()); },
        close: () => { track.stop(); stream.getTracks().forEach(t => t.stop()); },
        setDevice: async () => {}
      };
      this.localTracks.push(wrapper);
      return wrapper;
    }
    async publish() { return Promise.resolve(); }
    async leave() {
      this.localTracks.forEach(t => {
        try { t.stop?.(); } catch(e){}
      });
      this.localTracks = [];
      this.client = null;
    }
    getClient() { return this.client; }
  }

  window.IsometrikVideoCallProvider = IsometrikVideoCallProvider;
  window.createVideoCallProvider = function(config = {}) {
    const providerKey = (config.provider || "").toLowerCase();
    if (providerKey === "isometrik") {
      return new IsometrikVideoCallProvider({ wsUrl: config.wsUrl || "" });
    }
    if (providerKey === "livekit" && config.wsUrl) {
      return new LiveKitProvider({ wsUrl: config.wsUrl });
    }
    return new VideoCallProvider(config.AgoraRTC || window.AgoraRTC);
  };
  window.createLiveProvider = function(config = {}) {
    const providerKey = (config.provider || "").toLowerCase();
    if (providerKey === "livekit" && config.wsUrl) {
      return new LiveKitProvider({ wsUrl: config.wsUrl });
    }
    const agora = config.AgoraRTC || window.AgoraRTC;
    return new AgoraLiveProvider(agora);
  };
})(window);
