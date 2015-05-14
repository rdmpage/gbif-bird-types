# GBIF bird types
Analysis of holotypes for bird taxonomic names in GBIF.

As an exercise I searched GBIF for holotype specimens for birds. The search (13 May 2015) returned 11,664 records. I then filtered those on taxonomic names that GBIF could not match exactly (TAXON_MATCH_FUZZY) or names that GBIF could only match to a higher rank (TAXON_MATCH_HIGHERRANK). The query URL is:

http://www.gbif.org/occurrence/search?TAXON_KEY=212&TYPE_STATUS=HOLOTYPE&ISSUE=TAXON_MATCH_FUZZY&ISSUE=TAXON_MATCH_HIGHERRANK

This query found 6,928 records, so over half the bird holotype specimens in GBIF do not match a taxonomic name in GBIF.

To explore this further, the result of the query was downloaded (the download has DOI http://doi.org/10.15468/dl.vce3ay). I then wrote a script to parse the specimen records (in the file occurrence.txt in the download) and extract the GBIF occurrence id, catalogue number, and scientific name. I then used the GBIF API to retrieve (where available) the verbatim record for each specimen (using the URL http://api.gbif.org/v1/occurrence/<id>/verbatim where <id> is the occurrence id). This gives us the original name on the specimen, which I then looked up in BioNames using its API. If I got a hit I extracted the identifier of the name (the LSID in the ION database) and the corresponding publication id in BioNames (if available). If there was a publication associated with the name I then generated a human-readable citation using BioNames’s citeproc API.

The result of this mapping can be viewed at https://www.dropbox.com/s/4snvrhcw10fe5v2/all.html?dl=0: Of the 6,392 holotypes with names not recognised by GBIF, nearly half (3,165, 49.5%) exactly matched a name in ION. Many of these are also linked to the publication that published that name.
