FROM debian:bookworm-slim AS builder

ARG ASTERISK_VERSION=21-current

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    ca-certificates \
    curl \
    libedit-dev \
    libjansson-dev \
    libsqlite3-dev \
    libxml2-dev \
    libxslt1-dev \
    libncurses5-dev \
    libssl-dev \
    libsrtp2-dev \
    uuid-dev \
    unixodbc-dev \
    odbc-mariadb \
    pkg-config \
    python3 \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /usr/src

RUN curl -fsSL "https://downloads.asterisk.org/pub/telephony/asterisk/asterisk-${ASTERISK_VERSION}.tar.gz" \
    -o asterisk.tar.gz \
    && tar xzf asterisk.tar.gz \
    && rm asterisk.tar.gz \
    && mv asterisk-* asterisk

WORKDIR /usr/src/asterisk

# Configure (build deps already installed above, skip install_prereq)
RUN ./configure --with-pjproject-bundled --with-jansson-bundled \
    && make menuselect.makeopts

# Enable core sound packages (all modules built by default, loading controlled by modules.conf)
RUN menuselect/menuselect \
    --enable CORE-SOUNDS-EN-ULAW \
    menuselect.makeopts

RUN make -j$(nproc) && make install && make samples

# --- Runtime stage ---
FROM debian:bookworm-slim

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    libedit2 \
    libjansson4 \
    libsqlite3-0 \
    libxml2 \
    libxslt1.1 \
    libncurses6 \
    libssl3 \
    libsrtp2-1 \
    libuuid1 \
    unixodbc \
    odbc-mariadb \
    && rm -rf /var/lib/apt/lists/*

# Copy compiled Asterisk from builder
COPY --from=builder /usr/lib/asterisk/ /usr/lib/asterisk/
COPY --from=builder /usr/lib/libasterisk* /usr/lib/
COPY --from=builder /usr/sbin/asterisk /usr/sbin/asterisk
COPY --from=builder /usr/sbin/rasterisk /usr/sbin/rasterisk
COPY --from=builder /var/lib/asterisk/ /var/lib/asterisk/
COPY --from=builder /var/spool/asterisk/ /var/spool/asterisk/
COPY --from=builder /var/log/asterisk/ /var/log/asterisk/
COPY --from=builder /var/run/asterisk/ /var/run/asterisk/
COPY --from=builder /etc/asterisk/ /etc/asterisk/

# Register shared libraries
RUN ldconfig

# Create asterisk user
RUN groupadd -r asterisk && useradd -r -g asterisk -d /var/lib/asterisk -s /bin/false asterisk \
    && chown -R asterisk:asterisk /var/lib/asterisk /var/spool/asterisk /var/log/asterisk /var/run/asterisk /etc/asterisk

EXPOSE 5060/udp 5060/tcp 5061/tcp 10000-10100/udp

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["asterisk", "-fp"]
