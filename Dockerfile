FROM solr:6.6.3
MAINTAINER Timo Hund <timo.hund@dkd.de>
ENV TERM linux

RUN rm -fR /opt/solr/server/solr/*

COPY Resources/Private/Solr/ /opt/solr/server/solr

USER root

RUN echo 'SOLR_OPTS="$SOLR_OPTS -Dlog4j2.formatMsgNoLookups=true"' >>  bin/solr.in.sh

RUN mkdir -p /opt/solr/server/solr/data && \
    chown -R solr:solr /opt/solr/server/solr/

USER solr

VOLUME ["/opt/solr/server/solr/data"]
