import json
import sys
import geo
import path

def _get_distance(nodes, from_n, to_n):
    from_node = nodes[from_n]
    to_node = nodes[to_n]
    return geo.latlon_distance(from_node['lat'], from_node['lon'], to_node['lat'], to_node['lon'])

def get_nearest_port(nodes, lat, lon):
    min_dist = sys.maxint
    port = None

    for node in nodes:
        dist = geo.latlon_distance(node['lat'], node['lon'], lat, lon)
        if dist < min_dist:
            min_dist = dist
            port = node

    return port

def get_short_path(nodes, from_node, to_node):
    node_lookup = {}

    for node in nodes:
        node_id = node['node']
        node_lookup[node_id] = node

    graph = {}

    for node in nodes:
        node_id = node['node']
        edges = []
        for edge in node['edges']:
            cost = _get_distance(node_lookup, node_id, edge)
            edges.append((edge, cost))

        graph[node_id] = edges

    short_path = path.shortest_path(graph, from_node, to_node)

    result = []
    for node_id in short_path:
        result.append(node_lookup[node_id])

    return result

def read_json(path):
    with open(path) as data_file:    
        return json.load(data_file)

def write_json(path, nodes):
    with open(path, "w") as outfile:
        json.dump({ 'nodes': nodes }, outfile, indent=2)