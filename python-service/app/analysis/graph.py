"""
Membangun graf relasi antar-entitas (importir, eksportir, pelabuhan, perusahaan,
NPWP, dsb) dari data terstruktur, untuk mengungkap jaringan/kelompok yang
berpotensi terafiliasi dalam praktik penyelundupan atau manipulasi dokumen.
"""
import networkx as nx
import pandas as pd
from typing import Any


class GraphBuilder:

    def build(
        self,
        records: list[dict],
        source_field: str,
        target_field: str,
        weight_field: str | None = None,
    ) -> dict[str, Any]:
        df = pd.DataFrame(records)
        if df.empty or source_field not in df.columns or target_field not in df.columns:
            return {"nodes": [], "edges": [], "summary": "Field source/target tidak ditemukan pada data."}

        G = nx.Graph()

        for _, row in df.iterrows():
            src = str(row[source_field]).strip()
            tgt = str(row[target_field]).strip()
            if not src or not tgt or src.lower() == "nan" or tgt.lower() == "nan":
                continue

            weight = 1.0
            if weight_field and weight_field in df.columns:
                try:
                    weight = float(row[weight_field])
                except (ValueError, TypeError):
                    weight = 1.0

            if G.has_edge(src, tgt):
                G[src][tgt]["weight"] += weight
                G[src][tgt]["count"] += 1
            else:
                G.add_edge(src, tgt, weight=weight, count=1)

        if G.number_of_nodes() == 0:
            return {"nodes": [], "edges": [], "summary": "Tidak ada relasi valid yang dapat dibangun."}

        centrality = nx.degree_centrality(G)
        betweenness = nx.betweenness_centrality(G) if G.number_of_nodes() < 2000 else {}

        nodes = [
            {
                "id": n,
                "degree": G.degree(n),
                "degree_centrality": round(centrality.get(n, 0), 4),
                "betweenness_centrality": round(betweenness.get(n, 0), 4),
            }
            for n in G.nodes()
        ]
        nodes.sort(key=lambda x: x["degree"], reverse=True)

        edges = [
            {"source": u, "target": v, "weight": round(d["weight"], 2), "transaction_count": d["count"]}
            for u, v, d in G.edges(data=True)
        ]

        communities = self._detect_communities(G)

        return {
            "node_count": G.number_of_nodes(),
            "edge_count": G.number_of_edges(),
            "nodes": nodes,
            "edges": edges,
            "communities": communities,
            "top_hubs": nodes[:10],
            "summary": f"Graf berisi {G.number_of_nodes()} entitas dan {G.number_of_edges()} relasi, terbagi dalam {len(communities)} klaster/komunitas.",
        }

    @staticmethod
    def _detect_communities(G: nx.Graph) -> list[list[str]]:
        try:
            from networkx.algorithms.community import greedy_modularity_communities
            communities = greedy_modularity_communities(G)
            return [list(c) for c in communities]
        except Exception:
            return [list(c) for c in nx.connected_components(G)]
